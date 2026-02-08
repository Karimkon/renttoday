<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class Apartment extends Model
{
    use HasFactory;

    protected $fillable = [
        'number',
        'rooms',
        'rent',
        'tenant_id',
        'landlord_id',
        'location',
        'status'
    ];

    protected static function boot()
    {
        parent::boot();

        static::saving(function ($apartment) {
            if ($apartment->location) {
                $apartment->location = ucwords(strtolower(trim($apartment->location)));
            }
        });
    }

    public function tenant()
    {
        return $this->belongsTo(Tenant::class);
    }

    public function landlord()
    {
        return $this->belongsTo(Landlord::class);
    }

    public function payments()
    {
        return $this->hasMany(Payment::class);
    }

    /**
     * Get the ACTUAL amount paid in a specific calendar month.
     * Uses a unique filter to avoid double-counting the same payment record.
     *
     * @param string $month Format: Y-m (e.g., '2025-01')
     * @return float
     */
    public function getActualAmountPaidInMonth($month)
    {
        $payments = $this->getPaymentsActuallyMadeInMonth($month);
        return $payments->sum('amount');
    }

    /**
     * Get payments that were ACTUALLY made in a specific calendar month
     * (not allocated to that month, but physically received)
     *
     * IMPORTANT FIX: For advance payments that create multiple records (one per month),
     * we only count the PRIMARY record (where the payment's month matches the query month).
     * This prevents double-counting when a tenant pays multiple months at once.
     *
     * @param string $month Format: Y-m (e.g., '2025-01')
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getPaymentsActuallyMadeInMonth($month)
    {
        return $this->payments()
            ->select('payments.*')
            ->where('status', 'paid')
            ->where(function($query) use ($month) {
                $query->whereRaw("DATE_FORMAT(actual_payment_date, '%Y-%m') = ?", [$month])
                      ->orWhere(function($q) use ($month) {
                          $q->whereNull('actual_payment_date')
                            ->whereRaw("DATE_FORMAT(paid_at, '%Y-%m') = ?", [$month]);
                      });
            })
            ->get()
            ->unique('id')
            ->filter(function($payment) use ($month) {
                // FIX: For advance payments (split into multiple records),
                // we need to identify which is the PRIMARY record (the one representing actual cash inflow)

                if ($payment->is_advance_payment) {
                    $paymentDateMonth = $payment->actual_payment_date
                        ? $payment->actual_payment_date->format('Y-m')
                        : ($payment->paid_at ? $payment->paid_at->format('Y-m') : null);

                    $paymentMonth = $payment->month->format('Y-m');

                    // Case 1: This is the PRIMARY record (has original_amount set)
                    // Include only if month field matches actual payment date month
                    if ($payment->original_amount) {
                        return $paymentMonth === $paymentDateMonth;
                    }

                    // Case 2: This is a SECONDARY record (advance payment without original_amount)
                    // These represent future months covered by an advance - exclude from "actually made" list
                    // because no new cash was received for this specific record
                    // EXCEPTION: If month matches payment date month, it might be an old record format
                    if ($paymentMonth !== $paymentDateMonth) {
                        return false; // Exclude secondary records for future months
                    }
                }

                // Regular payments and properly matching advance payments are included
                return true;
            })
            ->values();
    }

    /**
     * Get the total ORIGINAL amount of cash received in a specific month
     * This accounts for advance payments correctly - counting the full original amount once
     *
     * @param string $month Format: Y-m (e.g., '2025-01')
     * @return float
     */
    public function getTotalCashReceivedInMonth($month)
    {
        $payments = $this->getPaymentsActuallyMadeInMonth($month);

        return $payments->sum(function($payment) {
            // For advance payments, return the original amount (full cash received)
            if ($payment->is_advance_payment && $payment->original_amount) {
                return $payment->original_amount;
            }
            // For regular payments, return the amount
            return $payment->amount;
        });
    }

    /**
     * NEW METHOD: Check if this month is covered by a PREVIOUS advance payment
     * (advance paid in an earlier month that covers this month)
     * 
     * @param string $month Format: Y-m (e.g., '2025-01')
     * @return Payment|null
     */
    public function getCoveringAdvancePayment($month)
    {
        return $this->payments()
            ->where('status', 'paid')
            ->where('is_advance_payment', true)
            ->whereJsonContains('allocated_months', $month)
            ->where(function($query) use ($month) {
                // Payment was made in a DIFFERENT month (earlier)
                $query->whereRaw("DATE_FORMAT(actual_payment_date, '%Y-%m') != ?", [$month])
                      ->orWhere(function($q) use ($month) {
                          $q->whereNull('actual_payment_date')
                            ->whereRaw("DATE_FORMAT(paid_at, '%Y-%m') != ?", [$month]);
                      });
            })
            ->first();
    }

    /**
     * UPDATED: Get payment status for reports
     * Now includes information about whether money was actually paid this month
     * FIX: Also checks if tenant existed during the report month
     */
    public function getPaymentStatusForReport($month)
    {
        $monthCarbon = Carbon::createFromFormat('Y-m', $month);
        $monthFormatted = $monthCarbon->format('Y-m');
        $monthEnd = $monthCarbon->copy()->endOfMonth();

        // Check if apartment has no tenant OR if tenant was created AFTER this month
        // This ensures past months show VACANT for new tenants
        if (!$this->tenant) {
            return [
                'status' => 'VACANT',
                'amount_paid' => 0,
                'actual_amount_this_month' => 0,
                'is_advance' => false,
                'months_covered' => 0,
                'payment_made_this_month' => false,
                'is_partial' => false,
                'advance_balance' => 0,
                'remaining_advance' => 0,
                'is_covered' => false,
                'covered_by_previous_advance' => false
            ];
        }

        // FIX: Check if tenant was assigned AFTER this report month
        // If tenant's first payment or creation date is after this month, consider it vacant
        $tenantFirstPayment = $this->payments()
            ->where('status', 'paid')
            ->where('tenant_id', $this->tenant_id)
            ->orderBy('month', 'asc')
            ->first();

        $tenantStartDate = $tenantFirstPayment
            ? Carbon::parse($tenantFirstPayment->month)->startOfMonth()
            : ($this->tenant->created_at ?? now());

        if ($tenantStartDate->startOfMonth() > $monthEnd) {
            return [
                'status' => 'VACANT',
                'amount_paid' => 0,
                'actual_amount_this_month' => 0,
                'is_advance' => false,
                'months_covered' => 0,
                'payment_made_this_month' => false,
                'is_partial' => false,
                'advance_balance' => 0,
                'remaining_advance' => 0,
                'is_covered' => false,
                'covered_by_previous_advance' => false
            ];
        }
        
        // Get payments ACTUALLY made this month (for commission)
        $paymentsActuallyMadeThisMonth = $this->getPaymentsActuallyMadeInMonth($monthFormatted);
        $actualAmountThisMonth = $paymentsActuallyMadeThisMonth->sum('amount');
        
        // Check if covered by a previous advance
        $coveringAdvance = $this->getCoveringAdvancePayment($monthFormatted);
        $coveredByPreviousAdvance = ($coveringAdvance !== null);
        
        // Get ALL payments for this apartment to check coverage
        $allPayments = $this->payments()
            ->where('status', 'paid')
            ->get();
        
        // Check if ANY payment covers this month
        $isCovered = false;
        $amountForThisMonth = 0;
        $isAdvance = false;
        $monthsCovered = 0;
        $paymentMadeThisMonth = false;
        
        foreach ($allPayments as $payment) {
            if ($payment->coversMonth($monthFormatted)) {
                $isCovered = true;
                $amountForThisMonth += $payment->getAmountForMonth($monthFormatted);
                
                $paidAt = $payment->actual_payment_date ?? $payment->paid_at;
                if ($paidAt && $paidAt->format('Y-m') === $monthFormatted) {
                    $paymentMadeThisMonth = true;
                }
                
                if ($payment->is_advance_payment) {
                    $isAdvance = true;
                    if ($payment->allocated_months && in_array($monthFormatted, $payment->allocated_months)) {
                        $monthsCovered = count($payment->allocated_months);
                    }
                }
            }
        }
        
        // Check tenant credit balance
        if (!$isCovered && ($this->tenant->credit_balance ?? 0) >= $this->rent) {
            $isCovered = true;
            $amountForThisMonth = $this->rent;
        }
        
        $rent = $this->rent;
        $status = 'UNPAID';
        $isPartial = false;

        if ($isCovered) {
            if ($amountForThisMonth >= $rent) {
                $status = 'PAID';
            } elseif ($amountForThisMonth > 0) {
                // FIX: Show PARTIAL status when amount is less than full rent
                $status = 'PARTIAL';
                $isPartial = true;
            }
        }

        return [
            'status' => $status,
            'amount_paid' => $amountForThisMonth, // Amount covering this specific month
            'actual_amount_this_month' => $actualAmountThisMonth, // NEW: Actual cash received this month (for commission)
            'is_covered' => $isCovered,
            'covered_by_previous_advance' => $coveredByPreviousAdvance, // NEW
            'is_advance' => $isAdvance,
            'months_covered' => $monthsCovered,
            'payment_made_this_month' => $paymentMadeThisMonth,
            'is_partial' => $isPartial,
            'advance_balance' => 0,
            'remaining_advance' => 0
        ];
    }

    /**
     * Get the payment record for displaying in report
     */
    public function getPaymentForMonth($month)
    {
        $monthFormatted = Carbon::createFromFormat('Y-m', $month)->format('Y-m');
        
        $payment = $this->payments()
            ->where('status', 'paid')
            ->get()
            ->first(function($payment) use ($monthFormatted) {
                return $payment->coversMonth($monthFormatted);
            });

        return $payment;
    }

    public function calculateExpectedRent($month = null)
    {
        $month = $month ? Carbon::parse($month) : now();
        $monthFormatted = $month->format('Y-m');
        
        if (!$this->tenant) {
            return 0;
        }
        
        $advancePayment = $this->payments()
            ->where('status', 'paid')
            ->where('is_advance_payment', true)
            ->get()
            ->first(function($payment) use ($monthFormatted) {
                return $payment->coversMonth($monthFormatted);
            });
        
        if ($advancePayment) {
            $paidAt = $advancePayment->actual_payment_date ?? $advancePayment->paid_at;
            if ($paidAt && $paidAt->month != $month->month) {
                return 0;
            }
        }
        
        return $this->rent;
    }

    public function getPaymentsCoveringMonth($month)
    {
        return $this->payments()
            ->where('status', 'paid')
            ->get()
            ->filter(function($payment) use ($month) {
                return $payment->coversMonth($month);
            });
    }

    public function getTotalAmountCoveringMonth($month)
    {
        $payments = $this->getPaymentsCoveringMonth($month);
        return $payments->sum(function($payment) use ($month) {
            return $payment->getAmountForMonth($month);
        });
    }

    /**
     * Get all data needed for a report row for a specific month.
     * Centralizing this logic ensures consistency between web view and PDF.
     * FIX: Added vacant check for tenants who joined after this month
     * FIX: Added partial status check when commissionable amount < rent
     */
    public function getReportRowData($month)
    {
        $monthCarbon = \Carbon\Carbon::parse($month);
        $monthFormatted = $monthCarbon->format('Y-m');
        $monthEnd = $monthCarbon->copy()->endOfMonth();

        // FIX: Check if apartment was vacant during this month
        // Either no tenant now, or tenant joined after this month
        $isVacantForMonth = false;
        if (!$this->tenant) {
            $isVacantForMonth = true;
        } else {
            // Check when tenant started (based on first payment or creation date)
            $tenantFirstPayment = $this->payments()
                ->where('status', 'paid')
                ->where('tenant_id', $this->tenant_id)
                ->orderBy('month', 'asc')
                ->first();

            $tenantStartDate = $tenantFirstPayment
                ? \Carbon\Carbon::parse($tenantFirstPayment->month)->startOfMonth()
                : ($this->tenant->created_at ?? now());

            if ($tenantStartDate->startOfMonth() > $monthEnd) {
                $isVacantForMonth = true;
            }
        }

        // 1. Get payments ACTUALLY MADE this month (cash inflow)
        $paymentsActuallyMade = $this->getPaymentsActuallyMadeInMonth($monthFormatted);

        // 2. Calculate Rent Collected (Agency) vs Direct to Landlord vs Commissionable
        // FIX: For advance payments, only count original_amount for the PRIMARY record
        // (the one where payment month matches actual payment date month)
        $rentCollectedByAgency = 0;
        $rentPaidDirectlyToLandlord = 0;
        $commissionableAmount = 0;

        foreach ($paymentsActuallyMade as $payment) {
            // FIX: Use original_amount only for the primary advance payment record
            // This prevents double-counting when advance payments create multiple records
            $amount = $payment->amount;
            if ($payment->is_advance_payment && $payment->original_amount) {
                // This is the primary record (already filtered by getPaymentsActuallyMadeInMonth)
                $amount = $payment->original_amount;
            }

            if ($payment->paid_to_landlord_directly) {
                $rentPaidDirectlyToLandlord += $amount;
            } else {
                $rentCollectedByAgency += $amount;
            }

            $commissionableAmount += $amount;
        }

        // 3. Check for previous advance coverage (no new money this month)
        $isCoveredByAdvance = false;
        $coveringAdvance = null;
        if ($commissionableAmount == 0) {
            $coveringAdvance = $this->getCoveringAdvancePayment($monthFormatted);
            if ($coveringAdvance) {
                $isCoveredByAdvance = true;
            }
        }

        // 4. Status and Date
        $status = 'UNPAID';
        $paymentDate = '-';
        $nextPayment = '-';

        // FIX: Check for vacancy FIRST using our calculated flag
        if ($isVacantForMonth) {
            $status = 'VACANT';
        } elseif ($commissionableAmount > 0) {
            // FIX: Check if this is a partial payment (amount < rent for this month)
            $amountCoveringThisMonth = $this->getTotalAmountCoveringMonth($monthFormatted);
            if ($amountCoveringThisMonth < $this->rent) {
                $status = 'PARTIAL';
            } else {
                $status = 'PAID';
            }
            $payment = $paymentsActuallyMade->first();
            $paymentDate = ($payment->actual_payment_date ?: $payment->paid_at)->format('jS/m/Y');
            
            $isAdvance = false;
            $adv = null;
            foreach($paymentsActuallyMade as $p) {
                if ($p->is_advance_payment) {
                    $isAdvance = true;
                    $adv = $p;
                    break;
                }
            }

            if ($isAdvance && $adv) {
                $count = count($adv->allocated_months ?? []);
                if ($count > 1) {
                    $status = $count . ' MONTHS ADVANCE PAID';
                }
                if ($adv->allocated_months) {
                    $last = \Carbon\Carbon::createFromFormat('Y-m', collect($adv->allocated_months)->sort()->last());
                    $nextPayment = $last->copy()->addMonth()->format('F Y');
                } else {
                    $nextPayment = $monthCarbon->copy()->addMonths($count ?: 1)->format('F Y');
                }
            } else {
                $nextPayment = $monthCarbon->copy()->addMonth()->format('F Y');
            }
        } elseif ($isCoveredByAdvance) {
            $status = 'ADVANCE COVER';
            $paymentDate = 'Paid: ' . ($coveringAdvance->actual_payment_date ?: $coveringAdvance->paid_at)->format('jS/m/Y');
            $nextPayment = $monthCarbon->copy()->addMonth()->format('F Y'); 
            if ($coveringAdvance->allocated_months) {
                $last = \Carbon\Carbon::createFromFormat('Y-m', collect($coveringAdvance->allocated_months)->sort()->last());
                $nextPayment = $last->copy()->addMonth()->format('F Y');
            }
        } else {
            // Check if any payment covers it (partial etc)
            $coveringTotal = $this->getTotalAmountCoveringMonth($monthFormatted);
            if ($coveringTotal > 0) {
                if ($coveringTotal >= $this->rent) {
                    $status = 'PAID';
                } else {
                    $status = 'PARTIAL';
                }
                $nextPayment = $monthCarbon->copy()->addMonth()->format('F Y');
            }
        }

        return [
            'apartment' => $this,
            'rent_collected_agency' => $rentCollectedByAgency,
            'rent_paid_direct' => $rentPaidDirectlyToLandlord,
            'commissionable_amount' => $commissionableAmount,
            'is_covered_by_advance' => $isCoveredByAdvance,
            'status_label' => $status,
            'payment_date' => $paymentDate,
            'next_payment' => $nextPayment,
            'covering_advance' => $coveringAdvance,
            'paid_directly' => $rentPaidDirectlyToLandlord > 0 && $rentCollectedByAgency == 0
        ];
    }
}
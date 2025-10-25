<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title> Apartments | Welcome</title>
<link href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;600;700;800&display=swap" rel="stylesheet">

<style>
:root {
  --accent: #0dcaf0;
  --light: #f9f9f9;
  --dark: #0a0a0d;
  --border: rgba(255,255,255,0.08);
  --transition: 500ms cubic-bezier(.2,.9,.3,1);
}

/* Reset */
* { margin: 0; padding: 0; box-sizing: border-box; }
body {
  font-family: 'Manrope', sans-serif;
  color: var(--light);
  min-height: 100vh;
  overflow: hidden;
  display: flex;
  align-items: center;
  justify-content: center;
  position: relative;
  background: #05080f;
}

/* Animated aurora gradient */
body::before {
  content: "";
  position: absolute;
  inset: 0;
  background: linear-gradient(120deg, #0a0a0d, #13243a, #0a0a0d, #092b37);
  background-size: 300% 300%;
  animation: aurora 18s ease-in-out infinite;
  opacity: 0.6;
  z-index: 0;
}
@keyframes aurora {
  0% { background-position: 0% 50%; }
  50% { background-position: 100% 50%; }
  100% { background-position: 0% 50%; }
}

/* Particle canvas */
#particles {
  position: absolute;
  inset: 0;
  z-index: 1;
  pointer-events: none;
  opacity: 0.45;
}

/* Card container */
.card {
  position: relative;
  z-index: 2;
  max-width: 1000px;
  width: 92%;
  padding: 70px 80px;
  border-radius: 20px;
  background: rgba(255,255,255,0.02);
  border: 1px solid var(--border);
  backdrop-filter: blur(24px) saturate(150%);
  box-shadow: 0 40px 120px rgba(0,0,0,0.6);
  display: flex;
  justify-content: space-between;
  align-items: center;
  gap: 60px;
  transition: transform var(--transition);
}
.card:hover { transform: scale(1.01); }

/* Left side */
.left { flex: 1; }
.brand-title {
  font-size: 48px;
  font-weight: 800;
  letter-spacing: -1px;
}
.brand-title span { color: var(--accent); }
.tagline {
  font-size: 18px;
  opacity: 0.75;
  margin-top: 14px;
  line-height: 1.6;
  max-width: 420px;
}

/* Buttons */
.buttons {
  margin-top: 40px;
  display: flex;
  gap: 18px;
}
.btn {
  text-decoration: none;
  font-weight: 600;
  font-size: 15px;
  padding: 14px 28px;
  border-radius: 10px;
  border: 1px solid var(--border);
  color: var(--light);
  background: linear-gradient(180deg, rgba(255,255,255,0.06), rgba(255,255,255,0.02));
  transition: all var(--transition);
}
.btn:hover {
  border-color: var(--accent);
  background: rgba(13,202,240,0.15);
  transform: translateY(-4px);
}
.btn.primary {
  background: var(--accent);
  color: #fff;
  border-color: var(--accent);
  box-shadow: 0 10px 30px rgba(13,202,240,0.25);
}
.btn.primary:hover {
  box-shadow: 0 18px 40px rgba(13,202,240,0.35);
}

/* Right image */
.right {
  flex: 1;
  display: flex;
  justify-content: center;
  align-items: center;
  position: relative;
}
.hero-img {
  width: 360px;
  height: 360px;
  border-radius: 50%;
  background: radial-gradient(circle at 30% 30%, #1b2538, #000);
  box-shadow: 0 0 60px rgba(13,202,240,0.15);
  overflow: hidden;
  position: relative;
}
.hero-img::before {
  content: "";
  position: absolute;
  inset: 0;
  background: url('images/renttoday.jpg') center/cover no-repeat;
  opacity: 0.8;
  filter: contrast(1.1) brightness(1);
}
.hero-img::after {
  content: "";
  position: absolute;
  inset: 0;
  background: radial-gradient(circle at 50% 80%, transparent 50%, rgba(0,0,0,0.9) 100%);
}

/* Footer */
.footer {
  position: absolute;
  bottom: 24px;
  left: 0; right: 0;
  text-align: center;
  font-size: 13px;
  color: rgba(255,255,255,0.45);
  letter-spacing: 0.5px;
}

/* Responsive */
@media (max-width: 900px) {
  .card { flex-direction: column; padding: 50px 30px; text-align: center; }
  .hero-img { width: 260px; height: 260px; margin-top: 40px; }
  .brand-title { font-size: 36px; }
  .tagline { margin: 0 auto; }
  .buttons { justify-content: center; }
}
</style>
</head>

<body>
<canvas id="particles"></canvas>

<div class="card">
 <div class="left">
    <h1 class="brand-title">Rent <span>Today</span></h1>
    <p class="tagline">Effortless property management and reliable rental payments—on time, every time.</p>
    <div class="buttons">
        <a href="{{ route('admin.login') }}" class="btn primary">Administrator Login</a>
        <a href="{{ route('secretary.login') }}" class="btn">Secretary Login</a>
        <a href="{{ route('tenant.login') }}" class="btn">Tenant Login</a>
    </div>
</div>

  <div class="right">
    <div class="hero-img"></div>
  </div>
</div>

<div class="footer">© {{ date('Y') }}  Apartments — Executive Management Suite</div>

<script>
/* Particle animation */
const canvas = document.getElementById('particles');
const ctx = canvas.getContext('2d');
let particles = [];

function resizeCanvas() {
  canvas.width = window.innerWidth;
  canvas.height = window.innerHeight;
}
window.addEventListener('resize', resizeCanvas);
resizeCanvas();

for (let i = 0; i < 60; i++) {
  particles.push({
    x: Math.random() * canvas.width,
    y: Math.random() * canvas.height,
    r: Math.random() * 2 + 0.5,
    dx: (Math.random() - 0.5) * 0.3,
    dy: (Math.random() - 0.5) * 0.3,
    opacity: Math.random() * 0.6 + 0.2
  });
}

function animate() {
  ctx.clearRect(0, 0, canvas.width, canvas.height);
  for (let p of particles) {
    ctx.beginPath();
    ctx.arc(p.x, p.y, p.r, 0, Math.PI * 2);
    ctx.fillStyle = `rgba(13,202,240,${p.opacity})`;
    ctx.fill();
    p.x += p.dx;
    p.y += p.dy;

    if (p.x < 0 || p.x > canvas.width) p.dx *= -1;
    if (p.y < 0 || p.y > canvas.height) p.dy *= -1;
  }
  requestAnimationFrame(animate);
}
animate();
</script>

</body>
</html>

/* NEBULA — фоновая 3D-сцена на Three.js (плагин: three) */
import * as THREE from './vendor/three.module.js';

const canvas = document.getElementById('webgl');
const renderer = new THREE.WebGLRenderer({ canvas, antialias: true, alpha: true });
renderer.setPixelRatio(Math.min(devicePixelRatio, 2));
renderer.setSize(innerWidth, innerHeight);

const scene = new THREE.Scene();
scene.fog = new THREE.FogExp2(0x05060c, 0.055);

const camera = new THREE.PerspectiveCamera(60, innerWidth / innerHeight, 0.1, 100);
camera.position.set(0, 0, 9);

/* --- свет --- */
scene.add(new THREE.AmbientLight(0x334, 0.6));
const key = new THREE.PointLight(0x7a5cff, 60, 40); key.position.set(4, 3, 6); scene.add(key);
const rim = new THREE.PointLight(0x22d3ee, 40, 40); rim.position.set(-5, -2, 4); scene.add(rim);
const pink = new THREE.PointLight(0xff4ecd, 25, 30); pink.position.set(0, 5, -4); scene.add(pink);

/* --- центральный «кристалл» (моделинг: икосаэдр + стекло) --- */
const gemMat = new THREE.MeshPhysicalMaterial({
  color: 0x9f8bff, metalness: 0.1, roughness: 0.15,
  transmission: 0.9, thickness: 1.5, ior: 1.6,
  clearcoat: 1, clearcoatRoughness: 0.2,
});
const gem = new THREE.Mesh(new THREE.IcosahedronGeometry(1.6, 1), gemMat);
scene.add(gem);

const wire = new THREE.Mesh(
  new THREE.IcosahedronGeometry(2.15, 1),
  new THREE.MeshBasicMaterial({ color: 0x22d3ee, wireframe: true, transparent: true, opacity: 0.18 })
);
scene.add(wire);

/* --- орбитальные спутники --- */
const orbits = new THREE.Group(); scene.add(orbits);
const satGeo = new THREE.OctahedronGeometry(0.16, 0);
for (let i = 0; i < 14; i++) {
  const m = new THREE.Mesh(satGeo, new THREE.MeshStandardMaterial({
    color: [0x7a5cff, 0x22d3ee, 0xff4ecd][i % 3],
    emissive: [0x3a2ccf, 0x0f7f99, 0x99116e][i % 3], roughness: 0.3,
  }));
  const r = 3 + Math.random() * 2.2, a = Math.random() * Math.PI * 2, y = (Math.random() - .5) * 3;
  m.userData = { r, a, y, s: 0.15 + Math.random() * 0.35 };
  orbits.add(m);
}

/* --- звёздное поле (particles) --- */
const starCount = 1800;
const pos = new Float32Array(starCount * 3);
for (let i = 0; i < starCount; i++) {
  pos[i * 3]     = (Math.random() - .5) * 60;
  pos[i * 3 + 1] = (Math.random() - .5) * 40;
  pos[i * 3 + 2] = -Math.random() * 40 - 2;
}
const stars = new THREE.Points(
  new THREE.BufferGeometry().setAttribute('position', new THREE.BufferAttribute(pos, 3)),
  new THREE.PointsMaterial({ color: 0xbfd0ff, size: 0.045, transparent: true, opacity: 0.8 })
);
scene.add(stars);

/* --- интерактив: мышь + скролл --- */
const mouse = { x: 0, y: 0, tx: 0, ty: 0 };
addEventListener('pointermove', e => {
  mouse.tx = (e.clientX / innerWidth - .5) * 2;
  mouse.ty = (e.clientY / innerHeight - .5) * 2;
});
let scrollY = 0;
addEventListener('scroll', () => { scrollY = window.scrollY; }, { passive: true });

/* --- цикл рендера --- */
const clock = new THREE.Clock();
function tick() {
  const t = clock.getElapsedTime();
  mouse.x += (mouse.tx - mouse.x) * 0.05;
  mouse.y += (mouse.ty - mouse.y) * 0.05;

  gem.rotation.x = t * 0.25 + mouse.y * 0.4;
  gem.rotation.y = t * 0.35 + mouse.x * 0.4;
  wire.rotation.x = -t * 0.12;
  wire.rotation.y = t * 0.18;

  orbits.children.forEach((m, i) => {
    const u = m.userData;
    u.a += u.s * 0.01;
    m.position.set(Math.cos(u.a) * u.r, u.y + Math.sin(t + i) * 0.3, Math.sin(u.a) * u.r);
    m.rotation.x = t + i; m.rotation.y = t * 0.7 + i;
  });

  stars.rotation.z = t * 0.01;

  /* камера «ныряет» при скролле */
  const sp = Math.min(scrollY / (document.body.scrollHeight - innerHeight || 1), 1);
  camera.position.z = 9 - sp * 4.5;
  camera.position.y = -sp * 1.5 + mouse.y * -0.4;
  camera.position.x = mouse.x * 0.6;
  camera.lookAt(0, 0, 0);

  renderer.render(scene, camera);
  requestAnimationFrame(tick);
}
tick();

addEventListener('resize', () => {
  camera.aspect = innerWidth / innerHeight;
  camera.updateProjectionMatrix();
  renderer.setSize(innerWidth, innerHeight);
});

<?php
declare(strict_types=1);

require dirname(__DIR__, 2) . '/src/bootstrap.php';

serve_asset('text/javascript', __FILE__);
?>
/* The sky, drawn in one WebGL canvas: the base gradient, the drifting nebula, a velvet texture and the light that
   follows the pointer (one fullscreen pass), PHP's star catalogue as points with their twinkles, glints, drift and
   parallax (one draw call), then the vignette and ten-frames-a-second film grain (one more pass). Three draws a
   frame on one layer, instead of a stack of full-screen layers the browser has to composite, which is what made the
   page glitch on a 4K screen.

   Without WebGL2 the server-drawn fallback in the page stays, and this only moves its light and parallax layers. */
(function () {
  "use strict";
  const doc = document, root = doc.documentElement;
  const sky = doc.querySelector(".sky");
  if (!sky) return;
  const canvas = doc.getElementById("skyGl");
  const reduce = matchMedia("(prefers-reduced-motion: reduce)").matches;
  const phone = matchMedia("(max-width: 640px)").matches;

  // the pointer, eased; when nobody has moved for a while the light wanders on its own
  let tx = innerWidth / 2, ty = innerHeight * 0.46, x = tx, y = ty, moved = -Infinity;
  addEventListener("pointermove", (e) => { tx = e.clientX; ty = e.clientY; moved = performance.now(); }, { passive: true });
  function follow(now) {
    if (now - moved > 3500) {
      const s = now / 1000;
      tx = innerWidth * (0.5 + 0.3 * Math.sin(s * 0.11));
      ty = innerHeight * (0.46 + 0.24 * Math.sin(s * 0.157 + 1.3));
    }
    x += (tx - x) * 0.055;
    y += (ty - y) * 0.055;
  }

  const FULL = "#version 300 es\nvoid main(){vec2 p=vec2(float((gl_VertexID<<1)&2),float(gl_VertexID&2));gl_Position=vec4(p*2.0-1.0,0.0,1.0);}";
  const COMMON = [
    "#version 300 es",
    "precision highp float;",
    "uniform vec2 uRes,uLight;uniform float uT,uScale;",
    "out vec4 o;",
    "float h21(vec2 p){vec3 q=fract(vec3(p.xyx)*.1031);q+=dot(q,q.yzx+33.33);return fract((q.x+q.y)*q.z);}",
    "vec2 cssPx(){return gl_FragCoord.xy/uScale;}",                                   // CSS px, origin bottom-left
    "vec2 cssUv(){vec2 v=uRes/uScale,p=cssPx();return vec2(p.x/v.x,1.-p.y/v.y);}",     // 0..1, origin top-left
  ].join("\n");
  const BG = COMMON + "\n" + [
    "float vn(vec2 p){vec2 i=floor(p),f=fract(p);f=f*f*(3.-2.*f);",
    "  return mix(mix(h21(i),h21(i+vec2(1,0)),f.x),mix(h21(i+vec2(0,1)),h21(i+vec2(1,1)),f.x),f.y);}",
    "float fbm(vec2 p){float s=0.,a=.5;for(int i=0;i<4;i++){s+=a*vn(p);p*=2.03;a*=.5;}return s;}",
    "vec3 over(vec3 c,vec3 col,float a){return mix(c,col,clamp(a,0.,1.));}",
    "float blob(vec2 uv,vec2 c,vec2 r){return 1.-smoothstep(0.,.72,length((uv-c)/r));}",
    "void main(){",
    "  vec2 uv=cssUv(),px=cssPx();",
    "  float e=length(vec2((uv.x-.5)/1.2,(uv.y-.46)/.95));",
    "  vec3 c=mix(vec3(7,8,12)/255.,vec3(3,3,5)/255.,smoothstep(0.,.55,e));",
    "  c=mix(c,vec3(1,1,2)/255.,smoothstep(.55,1.,e));",
    "  float k=1.-abs(fract(uT/140.)*2.-1.);k=k*k*(3.-2.*k);",                         // nebula: 70 s there, 70 s back
    "  vec2 n=uv-vec2(-.023,.017)*k;float fade=1.-.3*k,grow=1.+.05*k;",
    "  c=over(c,vec3(64,78,150)/255.,.1*fade*blob(n,vec2(.27,.64),vec2(.49,.35)*grow));",
    "  c=over(c,vec3(96,56,128)/255.,.075*fade*blob(n,vec2(.76,.34),vec2(.44,.32)*grow));",
    "  c=over(c,vec3(44,100,124)/255.,.055*fade*blob(n,vec2(.57,.5),vec2(.35,.28)*grow));",
    "  c=over(c,vec3(1),clamp(1.1*fbm(px*.011)-.3,0.,1.)*.035);",                      // velvet
    "  float d=clamp(length(px-uLight)/450.,0.,1.);",                                  // the light
    "  c=over(c,vec3(226,232,255)/255.,d<.5?mix(.05,.018,d/.5):mix(.018,0.,(d-.5)/.5));",
    "  o=vec4(c,1.);",
    "}",
  ].join("\n");
  const TOP = COMMON + "\n" + [
    "void main(){",
    "  vec2 uv=cssUv();",
    "  float e=length(vec2((uv.x-.5)/1.3,uv.y-.5));",
    "  float v=e<.42?0.:(e<.78?.55*(e-.42)/.36:mix(.55,.88,(e-.78)/.22));",
    "  float g=max(h21(floor(cssPx())+mod(floor(uT*10.),64.)*vec2(7.31,3.17))-.45,0.)*.08;",
    "  o=vec4(vec3(g),v);",                                                             // grain adds, vignette darkens
    "}",
  ].join("\n");
  const STAR_VS = [
    "#version 300 es",
    "layout(location=0) in vec4 aA;",          // x, y, radius, alpha
    "layout(location=1) in vec4 aB;",          // tint, layer, kind, period
    "layout(location=2) in float aC;",         // phase
    "uniform vec2 uView,uPar;uniform float uScale,uT;",
    "out vec3 vCol;out float vA,vR,vKind,vPeriod,vPhase,vSize,vLayer;",
    "void main(){",
    "  float layer=aB.y;",
    "  float depth=layer<.5?6.:(layer<1.5?14.:26.),period=layer<.5?200.:(layer<1.5?150.:110.);",
    "  float k=1.-abs(fract(uT/(2.*period))*2.-1.);",                                   // drift there and back
    "  vec2 pos=aA.xy*(uView+120.)-60.-uPar*depth-vec2(48.,30.)*k;",
    "  vec2 ndc=pos/uView*2.-1.;",
    "  gl_Position=vec4(ndc.x,-ndc.y,0.,1.);",
    "  vCol=aB.x<.5?vec3(1):(aB.x<1.5?vec3(.804,.847,1.):vec3(1.,.91,.784));",
    "  vA=aA.w;vR=aA.z;vKind=aB.z;vPeriod=aB.w;vPhase=aC;vLayer=layer;",
    "  vSize=aB.z>1.5?18.:(layer>1.5?aA.z*10.+2.:max(aA.z*5.,3.));",
    "  gl_PointSize=vSize*uScale;",
    "}",
  ].join("\n");
  const STAR_FS = [
    "#version 300 es",
    "precision highp float;",
    "in vec3 vCol;in float vA,vR,vKind,vPeriod,vPhase,vSize,vLayer;",
    "uniform float uT;",
    "out vec4 o;",
    "void main(){",
    "  vec2 q=(gl_PointCoord-.5)*vSize;float d2=dot(q,q),a;",                          // CSS px from the centre
    "  if(vKind>1.5){",                                                                   // a glint: a brief cross flare
    "    float f=fract(uT/vPeriod+vPhase),flash=smoothstep(.9,.94,f)*(1.-smoothstep(.94,1.,f));",
    "    float r=f*.87,cs=cos(r),sn=sin(r);",
    "    vec2 p=vec2(cs*q.x-sn*q.y,sn*q.x+cs*q.y)/mix(.3,1.,flash);",
    "    a=(exp(-abs(p.x)*2.)*exp(-abs(p.y)/3.)+exp(-abs(p.y)*2.)*exp(-abs(p.x)/3.))*.7+exp(-dot(p,p)*.8);",
    "    a*=flash;",
    "  }else{",
    "    float s=max(vR*.8,.45);",
    "    a=vA*min(1.,2.2*vR*vR/(2.*s*s))*exp(-d2/(2.*s*s));",                           // tiny stars read as bright as the SVG ones
    "    if(vLayer>1.5)a+=.05*exp(-d2/(2.*(vR*2.6)*(vR*2.6)));",                          // near stars get a halo
    "    if(vKind>.5)a*=mix(1.,.15,.5-.5*cos(6.2831853*(uT/vPeriod+vPhase)));",           // twinkle
    "  }",
    "  o=vec4(vCol*a,a);",
    "}",
  ].join("\n");

  function makeGL() {
    let c = null;
    try {
      c = canvas && canvas.getContext("webgl2", { alpha: false, antialias: false, depth: false, stencil: false,
        premultipliedAlpha: true, preserveDrawingBuffer: false, powerPreference: "low-power", failIfMajorPerformanceCaveat: true });
    } catch (_) { c = null; }
    if (!c) return null;

    const program = (vs, fs) => {
      const p = c.createProgram();
      for (const [type, src] of [[c.VERTEX_SHADER, vs], [c.FRAGMENT_SHADER, fs]]) {
        const s = c.createShader(type);
        c.shaderSource(s, src);
        c.compileShader(s);
        if (!c.getShaderParameter(s, c.COMPILE_STATUS)) { console.warn("sky shader:", c.getShaderInfoLog(s)); return null; }
        c.attachShader(p, s);
      }
      c.linkProgram(p);
      if (!c.getProgramParameter(p, c.LINK_STATUS)) { console.warn("sky program:", c.getProgramInfoLog(p)); return null; }
      const u = {};
      for (let i = 0, n = c.getProgramParameter(p, c.ACTIVE_UNIFORMS); i < n; i++) {
        const name = c.getActiveUniform(p, i).name;
        u[name] = c.getUniformLocation(p, name);
      }
      return { p, u };
    };
    const bg = program(FULL, BG), top = program(FULL, TOP), stars = program(STAR_VS, STAR_FS);
    if (!bg || !top || !stars) return null;

    let list = [];
    try { list = JSON.parse(doc.getElementById("skyData").textContent || "[]"); } catch (_) { list = []; }
    const buf = new Float32Array(list.length * 9);
    list.forEach((s, i) => buf.set(s, i * 9));
    const empty = c.createVertexArray();
    const vao = c.createVertexArray();
    c.bindVertexArray(vao);
    c.bindBuffer(c.ARRAY_BUFFER, c.createBuffer());
    c.bufferData(c.ARRAY_BUFFER, buf, c.STATIC_DRAW);
    c.enableVertexAttribArray(0);
    c.vertexAttribPointer(0, 4, c.FLOAT, false, 36, 0);
    c.enableVertexAttribArray(1);
    c.vertexAttribPointer(1, 4, c.FLOAT, false, 36, 16);
    c.enableVertexAttribArray(2);
    c.vertexAttribPointer(2, 1, c.FLOAT, false, 36, 32);
    c.bindVertexArray(null);

    canvas.addEventListener("webglcontextlost", (e) => {
      e.preventDefault();
      root.classList.remove("gl-sky");
      draw = makeDom();
    }, { once: true });
    root.classList.add("gl-sky");

    return function (t) {
      const w = innerWidth, h = innerHeight;
      // up to 1.5 device px per CSS px, and never more than about four million pixels, so a 4K screen stays cheap
      let scale = Math.min(devicePixelRatio || 1, 1.5);
      const budget = phone ? 2.2e6 : 4.2e6;
      if (w * h * scale * scale > budget) scale = Math.sqrt(budget / (w * h));
      const cw = Math.max(1, Math.round(w * scale)), ch = Math.max(1, Math.round(h * scale));
      if (canvas.width !== cw || canvas.height !== ch) { canvas.width = cw; canvas.height = ch; }
      const sx = cw / w;
      c.viewport(0, 0, cw, ch);

      c.disable(c.BLEND);
      c.useProgram(bg.p);
      c.uniform2f(bg.u.uRes, cw, ch);
      c.uniform1f(bg.u.uScale, sx);
      c.uniform1f(bg.u.uT, t);
      c.uniform2f(bg.u.uLight, x, h - y);
      c.bindVertexArray(empty);
      c.drawArrays(c.TRIANGLES, 0, 3);

      c.enable(c.BLEND);
      c.blendFunc(c.ONE, c.ONE_MINUS_SRC_ALPHA);
      c.useProgram(stars.p);
      c.uniform2f(stars.u.uView, w, h);
      c.uniform2f(stars.u.uPar, x / w - 0.5, y / h - 0.5);
      c.uniform1f(stars.u.uScale, sx);
      c.uniform1f(stars.u.uT, t);
      c.bindVertexArray(vao);
      c.drawArrays(c.POINTS, 0, list.length);

      c.useProgram(top.p);
      c.uniform2f(top.u.uRes, cw, ch);
      c.uniform1f(top.u.uScale, sx);
      c.uniform1f(top.u.uT, t);
      c.bindVertexArray(empty);
      c.drawArrays(c.TRIANGLES, 0, 3);
    };
  }

  /* the fallback: PHP's static layers, moved by transform only */
  function makeDom() {
    const light = sky.querySelector(".light");
    const layers = [...sky.querySelectorAll(".layer")].map((el) => ({ el, depth: Number(el.dataset.depth) || 0 }));
    return function () {
      if (light) light.style.transform = `translate3d(${x.toFixed(1)}px,${y.toFixed(1)}px,0)`;
      const px = x / innerWidth - 0.5, py = y / innerHeight - 0.5;
      for (const l of layers) l.el.style.transform = `translate3d(${(-px * l.depth).toFixed(2)}px,${(-py * l.depth).toFixed(2)}px,0)`;
    };
  }

  let draw = makeGL() || makeDom();
  const t0 = performance.now();
  if (reduce) {
    draw(0);
    addEventListener("resize", () => draw(0));
  } else {
    const loop = (now) => { follow(now); draw((now - t0) / 1000 + 37); requestAnimationFrame(loop); };
    requestAnimationFrame(loop);
  }
})();

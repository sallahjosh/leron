// Global interactions for Elorm's multi-page portfolio
// - Mobile nav toggle
// - Active nav highlighting
// - Basic page transition helper

(function(){
  const $ = (s, ctx=document) => ctx.querySelector(s);
  const $$ = (s, ctx=document) => Array.from(ctx.querySelectorAll(s));

  // Mobile nav toggle
  const navToggle = $("#nav-toggle");
  const navLinks = $("#nav-links");
  if (navToggle && navLinks){
    navToggle.addEventListener("click", ()=>{
      const open = navLinks.classList.toggle("open");
      navToggle.setAttribute("aria-expanded", String(open));
    });
    // Close after clicking a link (mobile)
    $$("a", navLinks).forEach(a=>a.addEventListener("click", ()=>{
      navLinks.classList.remove("open");
      navToggle.setAttribute("aria-expanded","false");
    }));
  }

  // Active link highlighting by pathname
  const path = window.location.pathname.replace(/\\/g,'/');
  $$(".nav-links a").forEach(a=>{
    const href = a.getAttribute('href');
    if (!href) return;
    const absolute = new URL(href, window.location.origin).pathname;
    if (absolute === path || (path.endsWith('/index.html') && absolute.endsWith('/index.html'))){
      a.classList.add('active');
    }
  });

  // Apply fade-in class to main content
  const page = $(".page");
  if (page) page.classList.add("fade-in");

  // Dynamic year
  const yearEl = $("#year");
  if (yearEl) yearEl.textContent = new Date().getFullYear();
})();

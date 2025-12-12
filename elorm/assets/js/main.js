// Elorm Ernest Sepenoo — Portfolio Interactions
// Smooth scrolling, mobile nav, reveal-on-scroll, dynamic year

(function(){
  const $ = (s, ctx=document) => ctx.querySelector(s);
  const $$ = (s, ctx=document) => Array.from(ctx.querySelectorAll(s));

  // Dynamic year in footer
  const yearEl = $("#year");
  if (yearEl) yearEl.textContent = new Date().getFullYear();

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

  // Smooth scroll for internal links (for browsers that don't support CSS smooth behavior reliably)
  $$("a[href^='#']").forEach(a=>{
    a.addEventListener("click", (e)=>{
      const id = a.getAttribute("href");
      if (!id || id === "#") return;
      const el = $(id);
      if (!el) return;
      e.preventDefault();
      el.scrollIntoView({behavior:"smooth", block:"start"});
    });
  });

  // Reveal on scroll using IntersectionObserver
  const revealables = [];
  $$("section, .card, .project, .product, .gallery-grid img").forEach(el=>{
    el.classList.add("reveal");
    revealables.push(el);
  });
  const io = new IntersectionObserver((entries)=>{
    entries.forEach(entry=>{
      if (entry.isIntersecting){
        entry.target.classList.add("in");
        io.unobserve(entry.target);
      }
    });
  }, {threshold: 0.12});
  revealables.forEach(el=>io.observe(el));

  // Basic client-side validation hint for contact form
  const form = $("#contact-form");
  if (form){
    form.addEventListener("submit", (e)=>{
      // If using mailto, allow default. Provide a quick validation check.
      const name = $("#name");
      const email = $("#email");
      const message = $("#message");
      if (!name.value.trim() || !email.value.trim() || !message.value.trim()){
        e.preventDefault();
        alert("Please fill in all fields before sending.");
      }
    });
  }
})();

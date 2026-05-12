// AMAANAT MEDICAL — Shared Navigation & Footer
// Includes: mobile hamburger menu, active link highlighting, shared footer

const NAV_LINKS = [
  { label: "Home", href: "index.html", key: "home" },
  { label: "About", href: "pages/about.html", key: "about" },
  { label: "Services", href: "pages/services.html", key: "services" },
  { label: "Equipment", href: "pages/equipment.html", key: "equipment" },
  { label: "Projects", href: "pages/projects.html", key: "projects" },
  { label: "Partners", href: "pages/partners.html", key: "partners" },
  { label: "Contact", href: "pages/contact.html", key: "contact" },
];

function renderNav(activePage, basePath = "") {
  const desktopLinks = NAV_LINKS.map((link) => {
    const href = basePath + link.href;
    const isActive = link.key === activePage;
    const cls = isActive
      ? "text-cyan-600 border-b-2 border-cyan-600 pb-1"
      : "text-slate-600 hover:text-blue-900 transition-colors";
    return `<a href="${href}" class="font-['Inter'] text-sm font-medium tracking-tight ${cls}">${link.label}</a>`;
  }).join("\n");

  const mobileLinks = NAV_LINKS.map((link) => {
    const href = basePath + link.href;
    const isActive = link.key === activePage;
    const cls = isActive
      ? "text-cyan-600 font-semibold bg-cyan-50 border-l-2 border-cyan-600 pl-3"
      : "text-slate-700 hover:text-blue-900 hover:bg-slate-50";
    return `<a href="${href}" class="block px-4 py-3 rounded-lg font-['Inter'] text-sm font-medium ${cls} transition-colors">${link.label}</a>`;
  }).join("\n");

  const logoHref = basePath + "index.html";
  const contactHref = basePath + "pages/contact.html";

  const navEl = document.getElementById("site-nav");
  navEl.style.overflow = "visible"; // allow dropdown to escape the header box

  navEl.innerHTML = `
    <div class="max-w-[1280px] mx-auto px-6 h-[80px] flex items-center justify-between">
      <a href="${logoHref}" class="text-xl font-bold tracking-tighter text-blue-900 shrink-0"><img style="width:6rem" src="/images/Amaanat Medical Secondary Logo.png"  > </a>

      <nav class="hidden md:flex items-center space-x-8">${desktopLinks}</nav>

      <div class="flex items-center gap-3">
        <a href="${contactHref}"
          class="hidden md:inline-flex bg-primary-container text-white px-6 py-2.5 rounded-lg font-['Inter'] text-sm font-semibold hover:opacity-90 active:scale-95 transition-all">
          Request a Quote
        </a>
        <button id="mobile-menu-btn"
          class="md:hidden flex flex-col justify-center items-center w-10 h-10 rounded-lg hover:bg-slate-100 transition-colors gap-[5px] shrink-0"
          aria-label="Open menu" aria-expanded="false">
          <span class="ham-line block w-6 h-[2px] bg-slate-700 rounded transition-all duration-300 origin-center"></span>
          <span class="ham-line block w-6 h-[2px] bg-slate-700 rounded transition-all duration-300"></span>
          <span class="ham-line block w-6 h-[2px] bg-slate-700 rounded transition-all duration-300 origin-center"></span>
        </button>
      </div>
    </div>

    <!-- Mobile dropdown — inside header, uses absolute positioning -->
    <div id="mobile-menu"
      class="md:hidden absolute top-full left-0 right-0 bg-white border-b border-slate-200 shadow-2xl"
      style="display:none; z-index:100;">
      <div class="max-w-[1280px] mx-auto px-4 py-4 space-y-1">
        ${mobileLinks}
        <div class="pt-3 mt-2 border-t border-slate-100">
          <a href="${contactHref}"
            class="block w-full text-center bg-primary-container text-white px-6 py-3 rounded-lg font-['Inter'] text-sm font-semibold hover:opacity-90 transition-all">
            Request a Quote
          </a>
        </div>
      </div>
    </div>
  `;

  const btn = document.getElementById("mobile-menu-btn");
  const menu = document.getElementById("mobile-menu");
  const lines = btn.querySelectorAll(".ham-line");

  function openMenu() {
    menu.style.display = "block";
    btn.setAttribute("aria-expanded", "true");
    btn.setAttribute("aria-label", "Close menu");
    lines[0].style.transform = "translateY(7px) rotate(45deg)";
    lines[1].style.opacity = "0";
    lines[2].style.transform = "translateY(-7px) rotate(-45deg)";
  }

  function closeMenu() {
    menu.style.display = "none";
    btn.setAttribute("aria-expanded", "false");
    btn.setAttribute("aria-label", "Open menu");
    lines[0].style.transform = "";
    lines[1].style.opacity = "1";
    lines[2].style.transform = "";
  }

  btn.addEventListener("click", (e) => {
    e.stopPropagation();
    menu.style.display === "none" ? openMenu() : closeMenu();
  });

  menu
    .querySelectorAll("a")
    .forEach((a) => a.addEventListener("click", closeMenu));
  document.addEventListener("click", (e) => {
    if (!btn.contains(e.target) && !menu.contains(e.target)) closeMenu();
  });
}

function renderFooter(basePath = "") {
  document.getElementById("site-footer").innerHTML = `
    <div class="max-w-[1280px] mx-auto px-8 grid grid-cols-1 md:grid-cols-4 gap-12">
      <div>
        <a href="${basePath}index.html" class="text-2xl font-black text-white mb-6 block"><img style="width:8rem" src="/images/Amaanat Medical Secondary Logo.png"></a>
        <p class="text-slate-300 font-['Inter'] text-sm leading-relaxed mb-6">
          Precision Engineering for Modern Healthcare Diagnostics. Empowering hospitals with technology since 2001.
        </p>
        <div class="flex gap-4">
          <span class="material-symbols-outlined text-slate-300 cursor-pointer hover:text-cyan-400 transition-colors">share</span>
          <span class="material-symbols-outlined text-slate-300 cursor-pointer hover:text-cyan-400 transition-colors">public</span>
          <span class="material-symbols-outlined text-slate-300 cursor-pointer hover:text-cyan-400 transition-colors">mail</span>
        </div>
      </div>
      <div>
        <h4 class="text-cyan-400 font-semibold mb-6 uppercase text-xs tracking-widest">Quick Links</h4>
        <ul class="space-y-4 text-sm">
          <li><a href="${basePath}pages/equipment.html" class="text-slate-300 hover:text-white inline-block hover:translate-x-1 duration-200 transition-all">Equipment Catalog</a></li>
          <li><a href="${basePath}pages/projects.html"  class="text-slate-300 hover:text-white inline-block hover:translate-x-1 duration-200 transition-all">Project Gallery</a></li>
          <li><a href="${basePath}pages/partners.html"  class="text-slate-300 hover:text-white inline-block hover:translate-x-1 duration-200 transition-all">OEM Partners</a></li>
          <li><a href="${basePath}pages/about.html"     class="text-slate-300 hover:text-white inline-block hover:translate-x-1 duration-200 transition-all">About Us</a></li>
        </ul>
      </div>
      <div>
        <h4 class="text-cyan-400 font-semibold mb-6 uppercase text-xs tracking-widest">Support</h4>
        <ul class="space-y-4 text-sm">
          <li><a href="${basePath}pages/contact.html"  class="text-slate-300 hover:text-white inline-block hover:translate-x-1 duration-200 transition-all">Technical Support</a></li>
          <li><a href="${basePath}pages/services.html" class="text-slate-300 hover:text-white inline-block hover:translate-x-1 duration-200 transition-all">Our Services</a></li>
          <li><a href="#" class="text-slate-300 hover:text-white inline-block hover:translate-x-1 duration-200 transition-all">Privacy Policy</a></li>
          <li><a href="#" class="text-slate-300 hover:text-white inline-block hover:translate-x-1 duration-200 transition-all">Terms of Service</a></li>
        </ul>
      </div>
      <div>
        <h4 class="text-cyan-400 font-semibold mb-6 uppercase text-xs tracking-widest">Headquarters</h4>
        <p class="text-slate-300 text-sm leading-relaxed mb-4">17, Kudirat Abiola Way, Opposite Oregun Bus Stop,<br/>Oregun, Ikeja, Lagos, Nigeria.<br/>(P.O. Box 7832, Ikeja)</p>
        <div class="flex items-center gap-2 text-cyan-400 font-semibold text-sm mb-2">
          <span class="material-symbols-outlined text-sm">call</span><span>08023011646 / 08035026442</span>
        </div>
        <div class="flex items-center gap-2 text-slate-300 text-sm">
          <span class="material-symbols-outlined text-sm">mail</span><span>amanaat@yahoo.com / amaanatnetwk@gmail.com</span>
        </div>
      </div>
    </div>
    <div class="max-w-[1280px] mx-auto px-8 mt-16 pt-8 border-t border-blue-800">
      <p class="text-slate-400 text-sm text-center">© 2025 AMAANAT MEDICAL DIAGNOSTICS EQUIPMENT LIMITED. All rights reserved.</p>
    </div>
  `;
}

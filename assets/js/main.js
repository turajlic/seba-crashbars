(function () {
  "use strict";

  /* meni — fullscreen overlay. Stanje se čuva i preko "open" klase (za
     animaciju) i preko "hidden" atributa (element potpuno van DOM prikaza i
     van dodirnih/klik interakcija dok je zatvoren — isti obrazac kao
     .nav-backdrop i .lightbox). Telo stranice se zaključava preko SOPSTVENE
     klase "nav-open", nezavisno od lightboxa (koji koristi "lb-open") — ni
     jedno ni drugo ne dira deljeni inline stil, pa zatvaranje jednog ne može
     da poremeti stanje drugog. */
  var toggle = document.querySelector(".nav-toggle");
  var nav = document.getElementById("mainnav");
  var backdrop = document.querySelector(".nav-backdrop");
  var closeBtn = document.querySelector(".nav-close");

  function openNav() {
    nav.hidden = false;
    backdrop.hidden = false;
    requestAnimationFrame(function () {
      nav.classList.add("open");
      backdrop.classList.add("show");
    });
    toggle.setAttribute("aria-expanded", "true");
    document.body.classList.add("nav-open");
  }
  function closeNav() {
    nav.classList.remove("open");
    backdrop.classList.remove("show");
    toggle.setAttribute("aria-expanded", "false");
    document.body.classList.remove("nav-open");
    setTimeout(function () { nav.hidden = true; backdrop.hidden = true; }, 260);
  }
  if (toggle && nav) {
    toggle.addEventListener("click", function () {
      nav.classList.contains("open") ? closeNav() : openNav();
    });
    if (closeBtn) closeBtn.addEventListener("click", closeNav);
    if (backdrop) backdrop.addEventListener("click", closeNav);
    /* klik na sam meni (pozadinu, ne link) takođe zatvara — meni sad pokriva ceo ekran */
    nav.addEventListener("click", function (e) {
      if (e.target === nav || e.target.tagName === "A") closeNav();
    });
    document.addEventListener("keydown", function (e) {
      if (e.key === "Escape" && nav.classList.contains("open")) closeNav();
    });
  }

  /* reveal na skrol */
  var reduced = window.matchMedia("(prefers-reduced-motion: reduce)").matches;
  var reveals = document.querySelectorAll(".reveal");
  if (reduced || !("IntersectionObserver" in window)) {
    reveals.forEach(function (el) { el.classList.add("in"); });
  } else {
    var io = new IntersectionObserver(function (entries) {
      entries.forEach(function (en) {
        if (en.isIntersecting) { en.target.classList.add("in"); io.unobserve(en.target); }
      });
    }, { threshold: 0.12 });
    reveals.forEach(function (el) { io.observe(el); });
  }

  /* lightbox — podržava galeriju (data-gallery) i pojedinačnu sliku (data-lightbox) */
  var lb = document.querySelector(".lightbox");
  if (lb) {
    var lbImg = lb.querySelector("img");
    var lbPrev = lb.querySelector(".lb-prev");
    var lbNext = lb.querySelector(".lb-next");
    var lbCount = lb.querySelector(".lb-count");
    var gallery = [];
    var galleryIndex = 0;
    var galleryName = "";

    function showLbImage() {
      lbImg.src = gallery[galleryIndex];
      lbImg.alt = galleryName;
      var multi = gallery.length > 1;
      lbPrev.hidden = !multi;
      lbNext.hidden = !multi;
      lbCount.hidden = !multi;
      if (multi) lbCount.textContent = (galleryIndex + 1) + "/" + gallery.length;
    }
    function openLb(images, name, startIndex) {
      gallery = images;
      galleryName = name || "";
      galleryIndex = startIndex || 0;
      showLbImage();
      lb.hidden = false;
      document.body.classList.add("lb-open");
    }
    function closeLb() {
      lb.hidden = true;
      lbImg.src = "";
      gallery = [];
      document.body.classList.remove("lb-open");
    }
    function lbNextImg() { galleryIndex = (galleryIndex + 1) % gallery.length; showLbImage(); }
    function lbPrevImg() { galleryIndex = (galleryIndex - 1 + gallery.length) % gallery.length; showLbImage(); }

    document.addEventListener("click", function (e) {
      var link = e.target.closest("[data-gallery]");
      if (link) {
        e.preventDefault();
        var images;
        try { images = JSON.parse(link.dataset.gallery); } catch (err) { images = []; }
        if (!images.length) return;
        openLb(images, link.dataset.galleryName, 0);
        return;
      }
      var legacy = e.target.closest("[data-lightbox]");
      if (legacy) {
        e.preventDefault();
        openLb([legacy.getAttribute("href")], "", 0);
      }
    });
    lb.addEventListener("click", function (e) {
      if (e.target === lb || e.target.closest(".lb-close")) { e.preventDefault(); closeLb(); return; }
      if (e.target.closest(".lb-next")) { e.preventDefault(); lbNextImg(); return; }
      if (e.target.closest(".lb-prev")) { e.preventDefault(); lbPrevImg(); }
    });
    document.addEventListener("keydown", function (e) {
      if (lb.hidden) return;
      if (e.key === "Escape") closeLb();
      else if (e.key === "ArrowRight" && gallery.length > 1) lbNextImg();
      else if (e.key === "ArrowLeft" && gallery.length > 1) lbPrevImg();
    });
  }
})();

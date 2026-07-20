(function () {
  "use strict";

  var grid = document.getElementById("radoviGrid");
  if (!grid) return;
  var filters = document.getElementById("radoviFilters");
  var search = document.getElementById("radoviSearch");
  var empty = document.getElementById("radoviEmpty");
  var cards = Array.prototype.slice.call(grid.querySelectorAll(".proj"));

  function activeBrand() {
    var btn = filters.querySelector(".filter-btn.is-active");
    return btn ? btn.dataset.brand : "";
  }

  function applyFilter() {
    var brand = activeBrand();
    var term = search.value.trim().toLowerCase();
    var shown = 0;
    cards.forEach(function (card) {
      var matchBrand = !brand || card.dataset.brand === brand;
      var matchSearch = !term || card.dataset.search.indexOf(term) !== -1;
      var show = matchBrand && matchSearch;
      card.hidden = !show;
      if (show) shown++;
    });
    if (empty) empty.hidden = shown !== 0;
  }

  function setActiveButton(brand) {
    var matched = false;
    filters.querySelectorAll(".filter-btn").forEach(function (b) {
      var isMatch = b.dataset.brand === brand;
      b.classList.toggle("is-active", isMatch);
      if (isMatch) matched = true;
    });
    if (!matched) {
      var sve = filters.querySelector('.filter-btn[data-brand=""]');
      if (sve) sve.classList.add("is-active");
    }
  }

  /* klik na dugme filtera — bez reload-a, samo menja URL (istorija/link i dalje rade) */
  filters.addEventListener("click", function (e) {
    var btn = e.target.closest(".filter-btn");
    if (!btn) return;
    e.preventDefault();
    setActiveButton(btn.dataset.brand);
    history.pushState(null, "", btn.getAttribute("href"));
    applyFilter();
  });

  if (search) search.addEventListener("input", applyFilter);

  /* dugme "nazad/napred" u browseru menja ?marka= — uskladi filter sa URL-om */
  window.addEventListener("popstate", function () {
    var params = new URLSearchParams(window.location.search);
    setActiveButton((params.get("marka") || "").toLowerCase());
    applyFilter();
  });

  /* inicijalno stanje već dolazi ispravno iz PHP-a (is-active klasa + server-side hidden
     za marku iz ?marka=...), ali svejedno primeni filter da uskladi i tekst pretrage
     (prazan na učitavanju) i da JS preuzme dalje ponašanje bez oslanjanja na reload */
  applyFilter();
})();

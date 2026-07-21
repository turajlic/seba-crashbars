(function () {
  "use strict";
  var CSRF = window.SEBA_CSRF;

  /* ---------- pomoćne ---------- */
  var toast = document.querySelector(".toast");
  var toastTimer;
  function showToast(msg, isError) {
    toast.textContent = msg;
    toast.classList.toggle("error", !!isError);
    toast.hidden = false;
    clearTimeout(toastTimer);
    toastTimer = setTimeout(function () { toast.hidden = true; }, 3200);
  }

  function post(data) {
    var body = new FormData();
    Object.keys(data).forEach(function (k) { body.append(k, data[k]); });
    body.append("csrf", CSRF);
    return fetch("save.php", { method: "POST", body: body })
      .then(function (r) { return r.json(); });
  }

  /* ---------- redosled i vidljivost sekcija ---------- */
  var list = document.getElementById("sectionList");

  function saveOrder() {
    var order = Array.prototype.map.call(list.querySelectorAll(".section-row"), function (row) {
      return { id: row.dataset.id, visible: row.querySelector(".vis-toggle").checked };
    });
    post({ action: "reorder", order: JSON.stringify(order) }).then(function (res) {
      showToast(res.ok ? "Redosled sačuvan." : (res.error || "Greška."), !res.ok);
    }).catch(function () { showToast("Greška u mreži.", true); });
  }

  new Sortable(list, { handle: ".row-bar .drag-handle", animation: 150, onEnd: saveOrder });
  list.addEventListener("change", function (e) {
    if (e.target.classList.contains("vis-toggle")) saveOrder();
  });

  /* ---------- otvaranje editora ---------- */
  list.addEventListener("click", function (e) {
    var btn = e.target.closest(".edit-toggle");
    if (!btn) return;
    var editor = btn.closest(".section-row").querySelector(".row-editor");
    editor.hidden = !editor.hidden;
    btn.textContent = editor.hidden ? "Izmeni" : "Zatvori";
  });

  /* ---------- stavke u listama (dodaj / ukloni / prevuci) ---------- */
  function wireImagesList(el) {
    new Sortable(el, { handle: ".image-row .drag-handle", animation: 150 });
  }
  document.querySelectorAll(".images-list").forEach(wireImagesList);

  document.querySelectorAll(".items-field").forEach(function (fs) {
    var itemsList = fs.querySelector(".items-list");
    new Sortable(itemsList, { handle: ".item-bar .drag-handle", animation: 150 });
    fs.querySelector(".item-add").addEventListener("click", function () {
      var tpl = fs.querySelector(".item-template");
      var clone = tpl.content.cloneNode(true);
      var newImagesLists = clone.querySelectorAll(".images-list");
      itemsList.appendChild(clone);
      newImagesLists.forEach(wireImagesList);
    });
    var expandAll = fs.querySelector(".items-expand-all");
    var collapseAll = fs.querySelector(".items-collapse-all");
    if (expandAll) expandAll.addEventListener("click", function () {
      itemsList.querySelectorAll(".item-card").forEach(function (c) { c.classList.remove("is-collapsed"); });
    });
    if (collapseAll) collapseAll.addEventListener("click", function () {
      itemsList.querySelectorAll(".item-card").forEach(function (c) { c.classList.add("is-collapsed"); });
    });
  });
  list.addEventListener("click", function (e) {
    var rm = e.target.closest(".item-remove");
    if (rm && confirm("Ukloniti ovu stavku?")) rm.closest(".item-card").remove();
  });

  /* sklapanje/rasklapanje pojedinačne stavke — klik na naslov (prvo tekstualno polje) */
  list.addEventListener("click", function (e) {
    var toggle = e.target.closest(".item-toggle");
    if (!toggle) return;
    toggle.closest(".item-card").classList.toggle("is-collapsed");
  });

  /* naslov skupljene stavke prati unos uživo, bez čekanja na snimanje */
  list.addEventListener("input", function (e) {
    var titleFld = e.target.closest("[data-item-title]");
    if (!titleFld) return;
    var card = titleFld.closest(".item-card");
    var toggle = card && card.querySelector(".item-toggle");
    if (!toggle) return;
    var val = titleFld.value.trim();
    toggle.textContent = val !== "" ? val.slice(0, 60) : "Nova stavka";
  });

  /* ---------- slike u galeriji rada (dodaj / ukloni) ---------- */
  list.addEventListener("click", function (e) {
    var addBtn = e.target.closest(".image-add");
    if (!addBtn) return;
    var imagesList = addBtn.closest(".images-fld").querySelector(".images-list");
    var tpl = document.getElementById("imageRowTemplate");
    imagesList.appendChild(tpl.content.cloneNode(true));
  });
  list.addEventListener("click", function (e) {
    var rm = e.target.closest(".image-remove");
    if (!rm) return;
    rm.closest(".image-row").remove();
  });

  /* ---------- upload slika ---------- */
  list.addEventListener("click", function (e) {
    var btn = e.target.closest(".img-upload");
    if (!btn) return;
    var wrapEl = btn.closest(".img-fld");
    var input = wrapEl.querySelector("input");
    var picker = document.createElement("input");
    picker.type = "file";
    picker.accept = "image/jpeg,image/png,image/webp";
    picker.addEventListener("change", function () {
      if (!picker.files[0]) return;
      btn.disabled = true;
      btn.textContent = "Otpremanje…";
      var body = new FormData();
      body.append("action", "upload");
      body.append("csrf", CSRF);
      body.append("image", picker.files[0]);
      fetch("save.php", { method: "POST", body: body })
        .then(function (r) { return r.json(); })
        .then(function (res) {
          if (res.ok) {
            input.value = res.path;
            var prev = wrapEl.querySelector(".img-preview");
            if (prev) { prev.src = "../" + res.path; prev.hidden = false; }
            showToast('Slika otpremljena. Ne zaboravi „Sačuvaj sekciju".');
          } else {
            showToast(res.error || "Otpremanje nije uspelo.", true);
          }
        })
        .catch(function () { showToast("Greška u mreži.", true); })
        .finally(function () { btn.disabled = false; btn.textContent = "Otpremi sliku"; });
    });
    picker.click();
  });

  /* ---------- čuvanje sekcije ---------- */
  function serializeEditor(form) {
    var data = {};
    /* prosta polja */
    form.querySelectorAll("[data-field]").forEach(function (el) {
      if (el.classList.contains("items-field")) {
        return; /* obrađuje se posebno ispod — nema .value na fieldset-u */
      } else if (el.classList.contains("img-fld")) {
        data[el.dataset.field] = el.querySelector("input").value.trim();
      } else if (!el.closest(".item-card")) {
        data[el.dataset.field] = el.value.trim();
      }
    });
    /* liste stavki */
    form.querySelectorAll(".items-field").forEach(function (fs) {
      var items = [];
      fs.querySelectorAll(".items-list .item-card").forEach(function (card) {
        var item = {};
        card.querySelectorAll("[data-key]").forEach(function (el) {
          if (el.classList.contains("images-fld")) {
            var imgs = [];
            el.querySelectorAll(".images-list .img-fld input").forEach(function (inp) {
              var v = inp.value.trim();
              if (v) imgs.push(v);
            });
            item[el.dataset.key] = imgs;
          } else if (el.classList.contains("img-fld")) {
            item[el.dataset.key] = el.querySelector("input").value.trim();
          } else {
            item[el.dataset.key] = el.value.trim();
          }
        });
        items.push(item);
      });
      data[fs.dataset.field] = items;
    });
    return data;
  }

  list.addEventListener("submit", function (e) {
    var form = e.target.closest(".row-editor");
    if (!form) return;
    e.preventDefault();
    var row = form.closest(".section-row");
    var status = form.querySelector(".save-status");
    status.textContent = "Čuvanje…";
    post({
      action: "save_section",
      section_id: row.dataset.id,
      data: JSON.stringify(serializeEditor(form))
    }).then(function (res) {
      status.textContent = "";
      showToast(res.ok ? "Sekcija sačuvana." : (res.error || "Greška."), !res.ok);
    }).catch(function () { status.textContent = ""; showToast("Greška u mreži.", true); });
  });

  /* ---------- prebacivanje prikaza (sidebar) ---------- */
  var sidebar = document.querySelector(".admin-sidebar");
  var sideLinks = document.querySelectorAll(".side-link");
  var views = document.querySelectorAll(".view");
  sideLinks.forEach(function (link) {
    link.addEventListener("click", function () {
      var target = link.dataset.view;
      sideLinks.forEach(function (l) { l.classList.toggle("is-active", l === link); });
      views.forEach(function (v) { v.classList.toggle("is-active", v.dataset.view === target); });
      if (sidebar) sidebar.classList.remove("open");
      window.scrollTo(0, 0);
    });
  });

  /* mobilni toggle sidebara */
  var menuToggle = document.querySelector(".admin-menu-toggle");
  if (menuToggle && sidebar) {
    menuToggle.addEventListener("click", function () { sidebar.classList.toggle("open"); });
    document.addEventListener("click", function (e) {
      if (sidebar.classList.contains("open") && !sidebar.contains(e.target) && e.target !== menuToggle) {
        sidebar.classList.remove("open");
      }
    });
  }

  document.getElementById("settingsForm").addEventListener("submit", function (e) {
    e.preventDefault();
    var data = { action: "save_settings" };
    new FormData(e.target).forEach(function (v, k) { data[k] = v; });
    post(data).then(function (res) {
      showToast(res.ok ? "Podešavanja sačuvana." : (res.error || "Greška."), !res.ok);
    }).catch(function () { showToast("Greška u mreži.", true); });
  });

  document.getElementById("passwordForm").addEventListener("submit", function (e) {
    e.preventDefault();
    var data = { action: "change_password" };
    new FormData(e.target).forEach(function (v, k) { data[k] = v; });
    post(data).then(function (res) {
      if (res.ok) { e.target.reset(); showToast("Lozinka promenjena."); }
      else showToast(res.error || "Greška.", true);
    }).catch(function () { showToast("Greška u mreži.", true); });
  });

  /* ---------- vraćanje na sigurnosnu kopiju ---------- */
  var backupsView = document.querySelector('.view[data-view="backups"]');
  if (backupsView) {
    backupsView.addEventListener("click", function (e) {
      var btn = e.target.closest(".backup-restore");
      if (!btn) return;
      if (!confirm("Vratiti sadržaj sajta na ovu raniju verziju? Trenutni sadržaj će prvo biti sačuvan kao nova kopija, pa se i ovo može poništiti.")) return;
      btn.disabled = true;
      btn.textContent = "Vraćanje…";
      post({ action: "restore_backup", file: btn.dataset.file }).then(function (res) {
        if (res.ok) {
          showToast("Sadržaj vraćen. Stranica se osvežava…");
          setTimeout(function () { window.location.reload(); }, 900);
        } else {
          showToast(res.error || "Greška.", true);
          btn.disabled = false;
          btn.textContent = "Vrati ovu verziju";
        }
      }).catch(function () {
        showToast("Greška u mreži.", true);
        btn.disabled = false;
        btn.textContent = "Vrati ovu verziju";
      });
    });
  }

  /* ---------- slike: brisanje, kopiranje putanje, samostalno otpremanje ---------- */
  var uploadsView = document.querySelector('.view[data-view="uploads"]');
  if (uploadsView) {
    uploadsView.addEventListener("click", function (e) {
      var copyBtn = e.target.closest(".upload-copy");
      if (copyBtn) {
        var path = copyBtn.dataset.path;
        var done = function () {
          var old = copyBtn.textContent;
          copyBtn.textContent = "Kopirano ✓";
          setTimeout(function () { copyBtn.textContent = old; }, 1500);
        };
        if (navigator.clipboard && navigator.clipboard.writeText) {
          navigator.clipboard.writeText(path).then(done).catch(function () {
            showToast("Kopiranje nije uspelo — putanja: " + path, true);
          });
        } else {
          showToast("Putanja: " + path);
        }
        return;
      }

      var delBtn = e.target.closest(".upload-delete");
      if (delBtn) {
        var file = delBtn.dataset.file;
        if (!confirm('Obrisati sliku "' + file + '"? Ovo se ne može poništiti.')) return;
        delBtn.disabled = true;
        delBtn.textContent = "Brisanje…";
        post({ action: "delete_upload", file: file }).then(function (res) {
          if (res.ok) {
            var card = delBtn.closest(".upload-card");
            if (card) card.remove();
            showToast("Slika obrisana.");
          } else {
            showToast(res.error || "Greška.", true);
            delBtn.disabled = false;
            delBtn.textContent = "Obriši";
          }
        }).catch(function () {
          showToast("Greška u mreži.", true);
          delBtn.disabled = false;
          delBtn.textContent = "Obriši";
        });
      }
    });

    var uploadNewBtn = document.getElementById("uploadNewImage");
    if (uploadNewBtn) {
      uploadNewBtn.addEventListener("click", function () {
        var picker = document.createElement("input");
        picker.type = "file";
        picker.accept = "image/jpeg,image/png,image/webp";
        picker.addEventListener("change", function () {
          if (!picker.files[0]) return;
          uploadNewBtn.disabled = true;
          var status = document.getElementById("uploadNewStatus");
          if (status) status.textContent = "Otpremanje…";
          var body = new FormData();
          body.append("action", "upload");
          body.append("csrf", CSRF);
          body.append("image", picker.files[0]);
          fetch("save.php", { method: "POST", body: body })
            .then(function (r) { return r.json(); })
            .then(function (res) {
              if (res.ok) {
                showToast("Slika otpremljena.");
                window.location.reload();
              } else {
                showToast(res.error || "Otpremanje nije uspelo.", true);
                uploadNewBtn.disabled = false;
                if (status) status.textContent = "";
              }
            })
            .catch(function () {
              showToast("Greška u mreži.", true);
              uploadNewBtn.disabled = false;
              if (status) status.textContent = "";
            });
        });
        picker.click();
      });
    }
  }
})();

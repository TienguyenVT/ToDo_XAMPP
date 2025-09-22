// Drag & drop has been disabled — Kanban renders as static columns.
document.addEventListener("DOMContentLoaded", function () {
  // Remove draggable attributes and prevent dragstart to ensure no accidental DnD behavior.
  document
    .querySelectorAll(".card-item[draggable], .kanban-task[draggable]")
    .forEach(function (el) {
      try {
        el.removeAttribute("draggable");
      } catch (e) {}
      el.addEventListener("dragstart", function (ev) {
        ev.preventDefault();
      });
    });
  +(
    // Remove any attached event listeners on columns by replacing nodes (safe, low-risk).
    document.querySelectorAll(".column").forEach(function (col) {
      var parent = col.parentNode;
      if (!parent) return;
      var clone = col.cloneNode(true);
      parent.replaceChild(clone, col);
    })
  );
});

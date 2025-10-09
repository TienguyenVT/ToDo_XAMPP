// File này có thể được sử dụng để thêm các tương tác JavaScript trong tương lai.
// Ví dụ: sử dụng AJAX để thêm/xóa công việc mà không cần tải lại trang.

// Helper: run init now if DOM already ready, or on DOMContentLoaded otherwise
function runWhenReady(fn) {
  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', fn);
  } else {
    // DOM already parsed
    fn();
  }
}

runWhenReady(function () {
  console.log("TodoWeb script loaded and ready.");
  // Xác nhận xóa công việc
  const deleteButtons = document.querySelectorAll('button[name="delete_task"]');
  deleteButtons.forEach(function (btn) {
    btn.addEventListener("click", function (e) {
      if (!confirm("Bạn có chắc chắn muốn xóa công việc này?")) {
        e.preventDefault();
      }
    });
  });

  // Kanban scroller buttons and keyboard support
  const kanbanScroller = document.getElementById("kanban-scroller");
  const btnLeft = document.querySelector(".kanban-scroll-left");
  const btnRight = document.querySelector(".kanban-scroll-right");
  const SCROLL_STEP = 360; // pixels per click (matches column width)

  if (kanbanScroller) {
    if (btnLeft)
      btnLeft.addEventListener("click", function () {
        kanbanScroller.scrollBy({ left: -SCROLL_STEP, behavior: "smooth" });
        kanbanScroller.focus();
      });
    if (btnRight)
      btnRight.addEventListener("click", function () {
        kanbanScroller.scrollBy({ left: SCROLL_STEP, behavior: "smooth" });
        kanbanScroller.focus();
      });

    // allow keyboard left/right when scroller is focused
    kanbanScroller.addEventListener("keydown", function (e) {
      if (e.key === "ArrowLeft") {
        e.preventDefault();
        kanbanScroller.scrollBy({ left: -SCROLL_STEP, behavior: "smooth" });
      } else if (e.key === "ArrowRight") {
        e.preventDefault();
        kanbanScroller.scrollBy({ left: SCROLL_STEP, behavior: "smooth" });
      }
    });
  }

  // --- Reminder Notification ---
  // Global displayed notifications set to avoid duplicates across polling/WebSocket
  window._displayedNotifications = window._displayedNotifications || new Set();

  // Unified generic alert renderer
  function showAlert(type, message, opts = {}) {
    const allowed = ['success', 'danger', 'warning', 'info'];
    const t = allowed.includes(type) ? type : 'info';
    const html = `<div class="alert alert-${t} alert-dismissible fade" role="alert">${message}
      <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>`;
    const container = document.getElementById('global-message-container');
    if (!container) return;

    // dedupe by provided key or message
    const key = opts.key || ('msg_' + message);
    if (window._displayedNotifications.has(key)) return;
    window._displayedNotifications.add(key);

    container.insertAdjacentHTML('beforeend', html);
    const last = container.lastElementChild;
    // trigger transition
    requestAnimationFrame(() => {
      last.classList.add('show');
      updatePushedContent();
    });
    scheduleAlertAutoDismiss(last, opts.autoDismissMs);
  }

  // Reminder-specific renderer. Accepts either a reminder_time (ISO) or a message text.
  function showReminderAlert(taskTitle, reminderPayload, opts = {}) {
    let messageText = '';
    // If reminderPayload looks like a datetime, format it; otherwise treat as message
    if (reminderPayload && !isNaN(Date.parse(reminderPayload))) {
      const timeStr = new Date(reminderPayload).toLocaleString('vi-VN', { hour12: false });
      messageText = `<strong>Nhắc nhở:</strong> Công việc <b>"${escapeHtml(taskTitle)}"</b> đến hạn lúc <b>${escapeHtml(timeStr)}</b>!`;
    } else if (typeof reminderPayload === 'string' && reminderPayload.trim() !== '') {
      messageText = `<strong>Nhắc nhở:</strong> Công việc <b>"${escapeHtml(taskTitle)}"</b>: ${escapeHtml(reminderPayload)}`;
    } else {
      messageText = `<strong>Nhắc nhở:</strong> Công việc <b>"${escapeHtml(taskTitle)}"</b> có nhắc nhở.`;
    }

    // Use notification id if provided for dedupe
    const key = opts.key || (`reminder_${opts.id || taskTitle}_${reminderPayload}`);
    showAlert('info', messageText, { key: key, autoDismissMs: opts.autoDismissMs || ALERT_AUTO_DISMISS_MS });
  }

  function escapeHtml(str) {
    return String(str)
      .replace(/&/g, '&amp;')
      .replace(/</g, '&lt;')
      .replace(/>/g, '&gt;')
      .replace(/"/g, '&quot;')
      .replace(/'/g, '&#039;');
  }

  // Export renderers to global so other scripts can call them (overrides header placeholders)
  window.showAlert = showAlert;
  window.showReminderAlert = showReminderAlert;

  function fetchReminders() {
    fetch('includes/components/notifications.php')
      .then(res => res.json())
      .then(data => {
        if (data.success && Array.isArray(data.notifications)) {
          data.notifications.forEach(n => {
            const key = `reminder_${n.task_id}_${n.reminder_time}`;
            if (!window._displayedNotifications.has(key)) {
              showReminderAlert(n.task_title, n.reminder_time, { id: n.task_id, key: key });
              // previously we ACKed displayed notifications; ACK endpoint removed, rely on read-only polling
            }
          });
        }
      }).catch(err => {
        console.error('fetchReminders error', err);
      });
  }

  // ACK mechanism removed — notifications are read-only and delivered by polling

  // Auto-dismiss alerts after N milliseconds
  const ALERT_AUTO_DISMISS_MS = 5000; // default auto-dismiss (ms)

  function scheduleAlertAutoDismiss(alertEl, ms) {
    if (!alertEl) return;
    // If it's already scheduled, skip
    if (alertEl.dataset.autodismiss) return;
    alertEl.dataset.autodismiss = '1';
  setTimeout(() => {
      try {
        // Use Bootstrap's alert dispose if available
        if (typeof bootstrap !== 'undefined' && bootstrap.Alert) {
          const a = bootstrap.Alert.getOrCreateInstance(alertEl);
          // when closed, update pushed content
          alertEl.addEventListener('closed.bs.alert', function () {
            updatePushedContent();
          }, { once: true });
          a.close();
        } else {
          alertEl.classList.remove('show');
          alertEl.classList.add('hide');
          alertEl.remove();
          updatePushedContent();
        }
      } catch (e) {
        alertEl.remove();
        updatePushedContent();
      }
    }, (typeof ms === 'number' && ms > 0) ? ms : ALERT_AUTO_DISMISS_MS);
  }

  // Move following content down by the total height of visible alerts
  function updatePushedContent() {
    const container = document.getElementById('global-message-container');
    if (!container) return;
    // compute total height of visible alerts
    const alerts = Array.from(container.querySelectorAll('.alert'));
    const total = alerts.reduce((sum, a) => sum + (a.offsetHeight || 0), 0);
    // apply transform only to the main content container (.container.mt-5)
    const main = document.querySelector('#global-message-container + .container.mt-3, #global-message-container + .container.mt-5, .container.mt-5');
    if (main) {
      main.style.transform = `translateY(${total}px)`;
    }
  }

  // Reset transform when alerts removed
  function resetPushedContent() {
    const container = document.getElementById('global-message-container');
    if (!container) return;
    const main = document.querySelector('#global-message-container + .container.mt-3, #global-message-container + .container.mt-5, .container.mt-5');
    if (main) main.style.transform = '';
  }

  // Schedule existing alerts on load
  document.querySelectorAll('#global-message-container .alert').forEach(scheduleAlertAutoDismiss);

  // Ensure reminders inserted later get auto-dismiss scheduled
  const origInsert = Element.prototype.insertAdjacentHTML;
  Element.prototype.insertAdjacentHTML = function(position, text) {
    origInsert.call(this, position, text);
    if (this.id === 'global-message-container') {
      // schedule the last child if it's an alert
      const last = this.lastElementChild;
      if (last && last.classList && last.classList.contains('alert')) {
        requestAnimationFrame(() => {
          last.classList.add('show');
          updatePushedContent();
        });
        scheduleAlertAutoDismiss(last);
      }
    }
  };

  // Process server-side flash messages enqueued on page by PHP
  function insertServerFlash(f) {
    try {
      const container = document.getElementById('global-message-container');
      if (!container) return;
      const type = f.type && ['success','danger','warning','info'].includes(f.type) ? f.type : 'info';
      const msg = f.msg || '';
      const html = `<div class="alert alert-${type} alert-dismissible fade" role="alert">${msg}<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button></div>`;
      container.insertAdjacentHTML('beforeend', html);
      const last = container.lastElementChild;
      if (last) {
        requestAnimationFrame(() => {
          last.classList.add('show');
          updatePushedContent();
        });
        scheduleAlertAutoDismiss(last);
      }
    } catch (e) {
      console.error('insertServerFlash error', e);
    }
  }

  if (window.__SERVER_FLASH__ && Array.isArray(window.__SERVER_FLASH__)) {
    window.__SERVER_FLASH__.forEach(insertServerFlash);
    // clear queue to avoid re-processing
    window.__SERVER_FLASH__ = [];
  }

  // Kiểm tra nhắc nhở mỗi 60 giây
  setInterval(fetchReminders, 60000);
  // Kiểm tra ngay khi load trang
  fetchReminders();
});

humhub.module('resource-booking.calendar', function (module, require, $) {
    var modal = require('ui.modal');

    var drag = null;
    var suppressClickUntil = 0;

    var init = function () {
        if ($(document).data('rbCalendarInit')) {
            return;
        }

        $(document).data('rbCalendarInit', true);

        $(document).on('change', '[data-rb-autosubmit] select, [data-rb-autosubmit] input[type="date"]', function () {
            var form = this.form;

            if (!form) {
                return;
            }

            if (typeof form.requestSubmit === 'function') {
                form.requestSubmit();
            } else {
                form.submit();
            }
        });

        $(document).on('pointerdown', '[data-rb-column] .rb-slot.is-bookable', onPointerDown);
        $(document).on('pointermove', onPointerMove);
        $(document).on('pointerup pointercancel', onPointerUp);
    };

    var onPointerDown = function (event) {
        if (!event.isPrimary || (event.pointerType && event.pointerType !== 'mouse' && event.pointerType !== 'pen')) {
            return;
        }

        if (typeof event.button === 'number' && event.button !== 0) {
            return;
        }

        var column = this.closest('[data-rb-column]');

        if (!column) {
            return;
        }

        var slots = Array.prototype.slice.call(column.querySelectorAll('.rb-slot'));
        var index = slots.indexOf(this);

        if (index < 0) {
            return;
        }

        drag = {
            pointerId: event.pointerId,
            column: column,
            slots: slots,
            slotHeight: parseFloat($(column).data('slot-height')) || 32,
            startIndex: index,
            startY: event.clientY,
            currentIndex: index,
            moved: false,
        };
    };

    var onPointerMove = function (event) {
        if (!drag || event.pointerId !== drag.pointerId) {
            return;
        }

        if (!drag.moved) {
            if (Math.abs(event.clientY - drag.startY) < 4) {
                return;
            }

            drag.moved = true;
            drag.column.classList.add('is-dragging');
        }

        if (event.cancelable) {
            event.preventDefault();
        }

        var rect = drag.column.getBoundingClientRect();
        var index = Math.floor((event.clientY - rect.top) / drag.slotHeight);
        index = Math.max(0, Math.min(drag.slots.length - 1, index));

        var target = drag.slots[index];

        // Never extend a selection over an unavailable slot (booked, closed or in the past).
        if (!target || !target.classList.contains('is-bookable') || index === drag.currentIndex) {
            return;
        }

        drag.currentIndex = index;
        paintSelection(drag);
    };

    var onPointerUp = function (event) {
        if (!drag || event.pointerId !== drag.pointerId) {
            return;
        }

        var state = drag;
        drag = null;

        clearSelection(state);

        if (event.type === 'pointercancel' || !state.moved) {
            return;
        }

        var min = Math.min(state.startIndex, state.currentIndex);
        var max = Math.max(state.startIndex, state.currentIndex);
        var startSlot = state.slots[min];
        var endSlot = state.slots[max];
        var baseUrl = state.column.getAttribute('data-create-url');

        if (!startSlot || !endSlot || !baseUrl) {
            return;
        }

        var url = baseUrl
            + (baseUrl.indexOf('?') >= 0 ? '&' : '?')
            + 'start_time=' + encodeURIComponent(startSlot.getAttribute('data-slot-time'))
            + '&end_time=' + encodeURIComponent(endSlot.getAttribute('data-slot-end'));

        suppressNextClick();
        openBookingModal(url);
    };

    var openBookingModal = function (url) {
        try {
            var promise = modal.global.load(url);

            if (promise && typeof promise.catch === 'function') {
                promise.catch(function () {
                    window.location.href = url;
                });
            }
        } catch (e) {
            window.location.href = url;
        }
    };

    var paintSelection = function (state) {
        var min = Math.min(state.startIndex, state.currentIndex);
        var max = Math.max(state.startIndex, state.currentIndex);
        var $hint = $(state.column).find('[data-rb-drag-hint]');

        $(state.column).find('.rb-slot.is-selected').removeClass('is-selected');

        for (var i = min; i <= max; i++) {
            if (state.slots[i]) {
                state.slots[i].classList.add('is-selected');
            }
        }

        var startSlot = state.slots[min];
        var endSlot = state.slots[max];

        if (startSlot && endSlot) {
            $hint
                .text(startSlot.getAttribute('data-slot-time') + ' – ' + endSlot.getAttribute('data-slot-end'))
                .css('top', (min * state.slotHeight) + 'px')
                .prop('hidden', false);
        }
    };

    var clearSelection = function (state) {
        $(state.column)
            .removeClass('is-dragging')
            .find('.rb-slot.is-selected')
            .removeClass('is-selected');

        $(state.column).find('[data-rb-drag-hint]').prop('hidden', true);
    };

    /**
     * A click event is fired after a drag as well. Swallow it in the capture phase so the
     * regular slot action (opening the create dialog for a single slot) does not run.
     */
    var suppressNextClick = function () {
        suppressClickUntil = Date.now() + 600;

        var blocker = function (event) {
            if (Date.now() > suppressClickUntil) {
                document.removeEventListener('click', blocker, true);
                return;
            }

            event.preventDefault();
            event.stopPropagation();
            document.removeEventListener('click', blocker, true);
        };

        document.addEventListener('click', blocker, true);

        setTimeout(function () {
            document.removeEventListener('click', blocker, true);
        }, 700);
    };

    module.export({
        init: init,
        initOnPjaxLoad: true,
    });
});

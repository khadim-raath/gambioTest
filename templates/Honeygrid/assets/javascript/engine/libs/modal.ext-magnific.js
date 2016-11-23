'use strict';

/* --------------------------------------------------------------
 modal.js 2016-02-23 
 Gambio GmbH
 http://www.gambio.de
 Copyright (c) 2016 Gambio GmbH
 Released under the GNU General Public License (Version 2)
 [http://www.gnu.org/licenses/gpl-2.0.html]
 --------------------------------------------------------------
 */

jse.libs.template.modal = jse.libs.template.modal || {};
jse.libs.template.modal.magnific = jse.libs.template.modal.magnific || {};

/**
 * ## Honeygrid Modal Magnific (Library Extension)
 * 
 * Library-function to open default modal layer. This function depends on jQuery & jQuery UI.
 *
 * @module Honeygrid/Libs/modal.ext-magnific
 * @exports jse.libs.modal.ext-magnific
 * @ignore
 */
(function (exports) {
	'use strict';

	var $document = $(document),
	    $body = $('body');

	var _openLayer = function _openLayer(dataset, deferred, getFormData, originalOptions) {

		var $wrap = null,
		    $bg = null,
		    $buttons = null,
		    $closeX = null,
		    $forms = null,
		    promise = deferred.promise(),
		    instance = null,
		    defaults = {
			preloader: false
		},
		    options = $.extend({}, defaults, dataset),
		    uid = parseInt(Math.random() * 100000);

		// ADD BUTTON INFORMATION
		$.each(options.buttons, function (i, v) {
			options.showButtons = true;
			v.index = i;
			v.uid = uid;
		});

		// GENERATE LAYER
		options.items.src = Mustache.render($('#magnific_wrapper').html(), options);

		$.magnificPopup.open(options);
		instance = $.magnificPopup.instance;

		// GET SELECTIONS
		$wrap = $(instance.wrap);
		$bg = $(instance.bgOverlay);
		$buttons = $wrap.find('.modal-footer button');
		$closeX = $wrap.find('button.mfp-close');

		// ########## EVENT HANDLER ##########

		// REMOVE MAGNIFIC EVENT HANDLER
		$wrap.off('click.mfp');
		$bg.off('click.mfp');
		$document.off('keyup.mfp');

		// BIND BUTTON HANDLER
		$buttons.each(function () {
			var $self = $(this),
			    data = $self.data();

			if (typeof data.index === 'number') {
				$self.on('click', dataset.buttons[data.index].event);
			}
		});

		// BIND EVENT HANDLER FOR THE CLOSE BUTTON
		$closeX.off('click').on('click', function (e) {
			e.stopPropagation();
			_rejectHandler($wrap, deferred, getFormData);
		});

		// BIND EVENT HANDLER FOR BACKGROUND LAYER
		if (dataset.closeOnBgClick) {

			$wrap.off('click').on('click', function (e) {
				if (!$(e.target).closest('.modal-dialog').length) {
					_rejectHandler($wrap, deferred, getFormData);
				}
			});
		}

		// BIND CLOSE HANDLER FOR ESC-KEY
		if (dataset.enableEscapeKey) {

			$document.on('keyup.magnific', function (e) {
				if (e.keyCode === 27) {
					_rejectHandler($wrap, deferred, getFormData);
				}
			});
		}

		// ADD A CLOSE LAYER METHOD TO THE PROMISE
		// TODO: TESTING
		deferred.close = function (success) {
			if (success) {
				_resolveHandler($wrap, deferred, getFormData);
			} else {
				_rejectHandler($wrap, deferred, getFormData);
			}
		};

		// EXECUTE ADDITIONAL FUNCTION CODE ON LAYER OPEN
		if (options.executeCode && typeof options.executeCode === 'function') {
			options.executeCode.call($wrap);
		}

		if (originalOptions.bootstrapClass !== undefined) {
			$wrap.find('.modal-dialog').addClass(originalOptions.bootstrapClass);
		}

		if (originalOptions.zIndex !== undefined) {
			$wrap.css('z-index', originalOptions.zIndex);
		}

		jse.libs.template.modal.finalizeLayer($wrap, originalOptions);

		return promise;
	};

	var _convertTemplate = function _convertTemplate(key, value) {
		var newValue = {
			src: value,
			type: 'inline'
		};

		return ['items', newValue];
	};

	var _getMapper = function _getMapper() {
		return {
			dialogClass: 'mainClass',
			modal: false,
			closeOnEscape: 'enableEscapeKey',
			closeOnOuter: 'closeOnBgClick',
			closeX: 'showCloseBtn',
			storeTemplate: false,
			template: _convertTemplate
		};
	};

	var _rejectHandler = function _rejectHandler($element, deferred, getFormData) {
		$element = $element.closest('.mfp-wrap');
		getFormData($element).always(function (result) {
			$document.off('keyup.magnific');
			deferred.reject(result);
			$.magnificPopup.close();
		});
	};

	var _resolveHandler = function _resolveHandler($element, deferred, getFormData) {
		$element = $element.closest('.mfp-wrap');
		getFormData($element, true).done(function (result) {
			$document.off('keyup.magnific');
			deferred.resolve(result);
			$.magnificPopup.close();
		});
	};

	// ########## VARIABLE EXPORT ##########

	exports.openLayer = _openLayer;
	exports.getMapper = _getMapper;
	exports.getResolveHandler = _resolveHandler;
	exports.getRejectHandler = _rejectHandler;
})(jse.libs.template.modal.magnific);
//# sourceMappingURL=data:application/json;charset=utf8;base64,eyJ2ZXJzaW9uIjozLCJzb3VyY2VzIjpbImxpYnMvbW9kYWwuZXh0LW1hZ25pZmljLmpzIl0sIm5hbWVzIjpbImpzZSIsImxpYnMiLCJ0ZW1wbGF0ZSIsIm1vZGFsIiwibWFnbmlmaWMiLCJleHBvcnRzIiwiJGRvY3VtZW50IiwiJCIsImRvY3VtZW50IiwiJGJvZHkiLCJfb3BlbkxheWVyIiwiZGF0YXNldCIsImRlZmVycmVkIiwiZ2V0Rm9ybURhdGEiLCJvcmlnaW5hbE9wdGlvbnMiLCIkd3JhcCIsIiRiZyIsIiRidXR0b25zIiwiJGNsb3NlWCIsIiRmb3JtcyIsInByb21pc2UiLCJpbnN0YW5jZSIsImRlZmF1bHRzIiwicHJlbG9hZGVyIiwib3B0aW9ucyIsImV4dGVuZCIsInVpZCIsInBhcnNlSW50IiwiTWF0aCIsInJhbmRvbSIsImVhY2giLCJidXR0b25zIiwiaSIsInYiLCJzaG93QnV0dG9ucyIsImluZGV4IiwiaXRlbXMiLCJzcmMiLCJNdXN0YWNoZSIsInJlbmRlciIsImh0bWwiLCJtYWduaWZpY1BvcHVwIiwib3BlbiIsIndyYXAiLCJiZ092ZXJsYXkiLCJmaW5kIiwib2ZmIiwiJHNlbGYiLCJkYXRhIiwib24iLCJldmVudCIsImUiLCJzdG9wUHJvcGFnYXRpb24iLCJfcmVqZWN0SGFuZGxlciIsImNsb3NlT25CZ0NsaWNrIiwidGFyZ2V0IiwiY2xvc2VzdCIsImxlbmd0aCIsImVuYWJsZUVzY2FwZUtleSIsImtleUNvZGUiLCJjbG9zZSIsInN1Y2Nlc3MiLCJfcmVzb2x2ZUhhbmRsZXIiLCJleGVjdXRlQ29kZSIsImNhbGwiLCJib290c3RyYXBDbGFzcyIsInVuZGVmaW5lZCIsImFkZENsYXNzIiwiekluZGV4IiwiY3NzIiwiZmluYWxpemVMYXllciIsIl9jb252ZXJ0VGVtcGxhdGUiLCJrZXkiLCJ2YWx1ZSIsIm5ld1ZhbHVlIiwidHlwZSIsIl9nZXRNYXBwZXIiLCJkaWFsb2dDbGFzcyIsImNsb3NlT25Fc2NhcGUiLCJjbG9zZU9uT3V0ZXIiLCJjbG9zZVgiLCJzdG9yZVRlbXBsYXRlIiwiJGVsZW1lbnQiLCJhbHdheXMiLCJyZXN1bHQiLCJyZWplY3QiLCJkb25lIiwicmVzb2x2ZSIsIm9wZW5MYXllciIsImdldE1hcHBlciIsImdldFJlc29sdmVIYW5kbGVyIiwiZ2V0UmVqZWN0SGFuZGxlciJdLCJtYXBwaW5ncyI6Ijs7QUFBQTs7Ozs7Ozs7OztBQVVBQSxJQUFJQyxJQUFKLENBQVNDLFFBQVQsQ0FBa0JDLEtBQWxCLEdBQTBCSCxJQUFJQyxJQUFKLENBQVNDLFFBQVQsQ0FBa0JDLEtBQWxCLElBQTJCLEVBQXJEO0FBQ0FILElBQUlDLElBQUosQ0FBU0MsUUFBVCxDQUFrQkMsS0FBbEIsQ0FBd0JDLFFBQXhCLEdBQW1DSixJQUFJQyxJQUFKLENBQVNDLFFBQVQsQ0FBa0JDLEtBQWxCLENBQXdCQyxRQUF4QixJQUFvQyxFQUF2RTs7QUFFQTs7Ozs7Ozs7O0FBU0MsV0FBU0MsT0FBVCxFQUFrQjtBQUNsQjs7QUFFQSxLQUFJQyxZQUFZQyxFQUFFQyxRQUFGLENBQWhCO0FBQUEsS0FDQ0MsUUFBUUYsRUFBRSxNQUFGLENBRFQ7O0FBR0EsS0FBSUcsYUFBYSxTQUFiQSxVQUFhLENBQVNDLE9BQVQsRUFBa0JDLFFBQWxCLEVBQTRCQyxXQUE1QixFQUF5Q0MsZUFBekMsRUFBMEQ7O0FBRTFFLE1BQUlDLFFBQVEsSUFBWjtBQUFBLE1BQ0NDLE1BQU0sSUFEUDtBQUFBLE1BRUNDLFdBQVcsSUFGWjtBQUFBLE1BR0NDLFVBQVUsSUFIWDtBQUFBLE1BSUNDLFNBQVMsSUFKVjtBQUFBLE1BS0NDLFVBQVVSLFNBQVNRLE9BQVQsRUFMWDtBQUFBLE1BTUNDLFdBQVcsSUFOWjtBQUFBLE1BT0NDLFdBQVc7QUFDVkMsY0FBVztBQURELEdBUFo7QUFBQSxNQVVDQyxVQUFVakIsRUFBRWtCLE1BQUYsQ0FBUyxFQUFULEVBQWFILFFBQWIsRUFBdUJYLE9BQXZCLENBVlg7QUFBQSxNQVdDZSxNQUFNQyxTQUFTQyxLQUFLQyxNQUFMLEtBQWdCLE1BQXpCLENBWFA7O0FBYUE7QUFDQXRCLElBQUV1QixJQUFG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LGFBQUYsQ0FBZ0JtQixLQUFoQjtBQUNBLEdBSkQ7QUFLQSxFQVBEOztBQVNBLEtBQUlFLGtCQUFrQixTQUFsQkEsZUFBa0IsQ0FBU29CLFFBQVQsRUFBbUJ0RSxRQUFuQixFQUE2QkMsV0FBN0IsRUFBMEM7QUFDL0RxRSxhQUFXQSxTQUFTMUIsT0FBVCxDQUFpQixXQUFqQixDQUFYO0FBQ0EzQyxjQUFZcUUsUUFBWixFQUFzQixJQUF0QixFQUE0QkksSUFBNUIsQ0FBaUMsVUFBU0YsTUFBVCxFQUFpQjtBQUNqRDlFLGFBQVV3QyxHQUFWLENBQWMsZ0JBQWQ7QUFDQWxDLFlBQVMyRSxPQUFULENBQWlCSCxNQUFqQjtBQUNBN0UsS0FBRWtDLGFBQUYsQ0FBZ0JtQixLQUFoQjtBQUNBLEdBSkQ7QUFLQSxFQVBEOztBQVVEOztBQUVDdkQsU0FBUW1GLFNBQVIsR0FBb0I5RSxVQUFwQjtBQUNBTCxTQUFRb0YsU0FBUixHQUFvQmIsVUFBcEI7QUFDQXZFLFNBQVFxRixpQkFBUixHQUE0QjVCLGVBQTVCO0FBQ0F6RCxTQUFRc0YsZ0JBQVIsR0FBMkJ0QyxjQUEzQjtBQUVBLENBdktBLEVBdUtDckQsSUFBSUMsSUFBSixDQUFTQyxRQUFULENBQWtCQyxLQUFsQixDQUF3QkMsUUF2S3pCLENBQUQiLCJmaWxlIjoibGlicy9tb2RhbC5leHQtbWFnbmlmaWMuanMiLCJzb3VyY2VzQ29udGVudCI6WyIvKiAtLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLVxuIG1vZGFsLmpzIDIwMTYtMDItMjMgXG4gR2FtYmlvIEdtYkhcbiBodHRwOi8vd3d3LmdhbWJpby5kZVxuIENvcHlyaWdodCAoYykgMjAxNiBHYW1iaW8gR21iSFxuIFJlbGVhc2VkIHVuZGVyIHRoZSBHTlUgR2VuZXJhbCBQdWJsaWMgTGljZW5zZSAoVmVyc2lvbiAyKVxuIFtodHRwOi8vd3d3LmdudS5vcmcvbGljZW5zZXMvZ3BsLTIuMC5odG1sXVxuIC0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tXG4gKi9cblxuanNlLmxpYnMudGVtcGxhdGUubW9kYWwgPSBqc2UubGlicy50ZW1wbGF0ZS5tb2RhbCB8fCB7fTtcbmpzZS5saWJzLnRlbXBsYXRlLm1vZGFsLm1hZ25pZmljID0ganNlLmxpYnMudGVtcGxhdGUubW9kYWwubWFnbmlmaWMgfHwge307XG5cbi8qKlxuICogIyMgSG9uZXlncmlkIE1vZGFsIE1hZ25pZmljIChMaWJyYXJ5IEV4dGVuc2lvbilcbiAqIFxuICogTGlicmFyeS1mdW5jdGlvbiB0byBvcGVuIGRlZmF1bHQgbW9kYWwgbGF5ZXIuIFRoaXMgZnVuY3Rpb24gZGVwZW5kcyBvbiBqUXVlcnkgJiBqUXVlcnkgVUkuXG4gKlxuICogQG1vZHVsZSBIb25leWdyaWQvTGlicy9tb2RhbC5leHQtbWFnbmlmaWNcbiAqIEBleHBvcnRzIGpzZS5saWJzLm1vZGFsLmV4dC1tYWduaWZpY1xuICogQGlnbm9yZVxuICovXG4oZnVuY3Rpb24oZXhwb3J0cykge1xuXHQndXNlIHN0cmljdCc7XG5cblx0dmFyICRkb2N1bWVudCA9ICQoZG9jdW1lbnQpLFxuXHRcdCRib2R5ID0gJCgnYm9keScpO1xuXG5cdHZhciBfb3BlbkxheWVyID0gZnVuY3Rpb24oZGF0YXNldCwgZGVmZXJyZWQsIGdldEZvcm1EYXRhLCBvcmlnaW5hbE9wdGlvbnMpIHtcblxuXHRcdHZhciAkd3JhcCA9IG51bGwsXG5cdFx0XHQkYmcgPSBudWxsLFxuXHRcdFx0JGJ1dHRvbnMgPSBudWxsLFxuXHRcdFx0JGNsb3NlWCA9IG51bGwsXG5cdFx0XHQkZm9ybXMgPSBudWxsLFxuXHRcdFx0cHJvbWlzZSA9IGRlZmVycmVkLnByb21pc2UoKSxcblx0XHRcdGluc3RhbmNlID0gbnVsbCxcblx0XHRcdGRlZmF1bHRzID0ge1xuXHRcdFx0XHRwcmVsb2FkZXI6IGZhbHNlXG5cdFx0XHR9LFxuXHRcdFx0b3B0aW9ucyA9ICQuZXh0ZW5kKHt9LCBkZWZhdWx0cywgZGF0YXNldCksXG5cdFx0XHR1aWQgPSBwYXJzZUludChNYXRoLnJhbmRvbSgpICogMTAwMDAwKTtcblxuXHRcdC8vIEFERCBCVVRUT04gSU5GT1JNQVRJT05cblx0XHQkLmVhY2gob3B0aW9ucy5idXR0b25zLCBmdW5jdGlvbihpLCB2KSB7XG5cdFx0XHRvcHRpb25zLnNob3dCdXR0b25zID0gdHJ1ZTtcblx0XHRcdHYuaW5kZXggPSBpO1xuXHRcdFx0di51aWQgPSB1aWQ7XG5cdFx0fSk7XG5cblx0XHQvLyBHRU5FUkFURSBMQVlFUlxuXHRcdG9wdGlvbnMuaXRlbXMuc3JjID0gTXVzdGFjaGUucmVuZGVyKCQoJyNtYWduaWZpY193cmFwcGVyJykuaHRtbCgpLCBvcHRpb25zKTtcblxuXHRcdCQubWFnbmlmaWNQb3B1cC5vcGVuKG9wdGlvbnMpO1xuXHRcdGluc3RhbmNlID0gJC5tYWduaWZpY1BvcHVwLmluc3RhbmNlO1xuXG5cdFx0Ly8gR0VUIFNFTEVDVElPTlNcblx0XHQkd3JhcCA9ICQoaW5zdGFuY2Uud3JhcCk7XG5cdFx0JGJnID0gJChpbnN0YW5jZS5iZ092ZXJsYXkpO1xuXHRcdCRidXR0b25zID0gJHdyYXAuZmluZCgnLm1vZGFsLWZvb3RlciBidXR0b24nKTtcblx0XHQkY2xvc2VYID0gJHdyYXAuZmluZCgnYnV0dG9uLm1mcC1jbG9zZScpO1xuXG4vLyAjIyMjIyMjIyMjIEVWRU5UIEhBTkRMRVIgIyMjIyMjIyMjI1xuXG5cdFx0Ly8gUkVNT1ZFIE1BR05JRklDIEVWRU5UIEhBTkRMRVJcblx0XHQkd3JhcC5vZmYoJ2NsaWNrLm1mcCcpO1xuXHRcdCRiZy5vZmYoJ2NsaWNrLm1mcCcpO1xuXHRcdCRkb2N1bWVudC5vZmYoJ2tleXVwLm1mcCcpO1xuXG5cblx0XHQvLyBCSU5EIEJVVFRPTiBIQU5ETEVSXG5cdFx0JGJ1dHRvbnMuZWFjaChmdW5jdGlvbigpIHtcblx0XHRcdHZhciAkc2VsZiA9ICQodGhpcyksXG5cdFx0XHRcdGRhdGEgPSAkc2VsZi5kYXRhKCk7XG5cblx0XHRcdGlmICh0eXBlb2YgZGF0YS5pbmRleCA9PT0gJ251bWJlcicpIHtcblx0XHRcdFx0JHNlbGYub24oJ2NsaWNrJywgZGF0YXNldC5idXR0b25zW2RhdGEuaW5kZXhdLmV2ZW50KTtcblx0XHRcdH1cblx0XHR9KTtcblxuXHRcdC8vIEJJTkQgRVZFTlQgSEFORExFUiBGT1IgVEhFIENMT1NFIEJVVFRPTlxuXHRcdCRjbG9zZVhcblx0XHRcdC5vZmYoJ2NsaWNrJylcblx0XHRcdC5vbignY2xpY2snLCBmdW5jdGlvbihlKSB7XG5cdFx0XHRcdGUuc3RvcFByb3BhZ2F0aW9uKCk7XG5cdFx0XHRcdF9yZWplY3RIYW5kbGVyKCR3cmFwLCBkZWZlcnJlZCwgZ2V0Rm9ybURhdGEpO1xuXHRcdFx0fSk7XG5cblx0XHQvLyBCSU5EIEVWRU5UIEhBTkRMRVIgRk9SIEJBQ0tHUk9VTkQgTEFZRVJcblx0XHRpZiAoZGF0YXNldC5jbG9zZU9uQmdDbGljaykge1xuXG5cdFx0XHQkd3JhcFxuXHRcdFx0XHQub2ZmKCdjbGljaycpXG5cdFx0XHRcdC5vbignY2xpY2snLCBmdW5jdGlvbihlKSB7XG5cdFx0XHRcdFx0aWYgKCEkKGUudGFyZ2V0KS5jbG9zZXN0KCcubW9kYWwtZGlhbG9nJykubGVuZ3RoKSB7XG5cdFx0XHRcdFx0XHRfcmVqZWN0SGFuZGxlcigkd3JhcCwgZGVmZXJyZWQsIGdldEZvcm1EYXRhKTtcblx0XHRcdFx0XHR9XG5cdFx0XHRcdH0pO1xuXG5cdFx0fVxuXG5cdFx0Ly8gQklORCBDTE9TRSBIQU5ETEVSIEZPUiBFU0MtS0VZXG5cdFx0aWYgKGRhdGFzZXQuZW5hYmxlRXNjYXBlS2V5KSB7XG5cblx0XHRcdCRkb2N1bWVudFxuXHRcdFx0XHQub24oJ2tleXVwLm1hZ25pZmljJywgZnVuY3Rpb24oZSkge1xuXHRcdFx0XHRcdGlmIChlLmtleUNvZGUgPT09IDI3KSB7XG5cdFx0XHRcdFx0XHRfcmVqZWN0SGFuZGxlcigkd3JhcCwgZGVmZXJyZWQsIGdldEZvcm1EYXRhKTtcblx0XHRcdFx0XHR9XG5cdFx0XHRcdH0pO1xuXG5cdFx0fVxuXG5cdFx0Ly8gQUREIEEgQ0xPU0UgTEFZRVIgTUVUSE9EIFRPIFRIRSBQUk9NSVNFXG5cdFx0Ly8gVE9ETzogVEVTVElOR1xuXHRcdGRlZmVycmVkLmNsb3NlID0gZnVuY3Rpb24oc3VjY2Vzcykge1xuXHRcdFx0aWYgKHN1Y2Nlc3MpIHtcblx0XHRcdFx0X3Jlc29sdmVIYW5kbGVyKCR3cmFwLCBkZWZlcnJlZCwgZ2V0Rm9ybURhdGEpO1xuXHRcdFx0fSBlbHNlIHtcblx0XHRcdFx0X3JlamVjdEhhbmRsZXIoJHdyYXAsIGRlZmVycmVkLCBnZXRGb3JtRGF0YSk7XG5cdFx0XHR9XG5cdFx0fTtcblxuXHRcdC8vIEVYRUNVVEUgQURESVRJT05BTCBGVU5DVElPTiBDT0RFIE9OIExBWUVSIE9QRU5cblx0XHRpZiAob3B0aW9ucy5leGVjdXRlQ29kZSAmJiB0eXBlb2Ygb3B0aW9ucy5leGVjdXRlQ29kZSA9PT0gJ2Z1bmN0aW9uJykge1xuXHRcdFx0b3B0aW9ucy5leGVjdXRlQ29kZS5jYWxsKCR3cmFwKTtcblx0XHR9XG5cblxuXHRcdGlmIChvcmlnaW5hbE9wdGlvbnMuYm9vdHN0cmFwQ2xhc3MgIT09IHVuZGVmaW5lZCkge1xuXHRcdFx0JHdyYXAuZmluZCgnLm1vZGFsLWRpYWxvZycpLmFkZENsYXNzKG9yaWdpbmFsT3B0aW9ucy5ib290c3RyYXBDbGFzcyk7XG5cdFx0fVxuXG5cdFx0aWYgKG9yaWdpbmFsT3B0aW9ucy56SW5kZXggIT09IHVuZGVmaW5lZCkge1xuXHRcdFx0JHdyYXAuY3NzKCd6LWluZGV4Jywgb3JpZ2luYWxPcHRpb25zLnpJbmRleCk7XG5cdFx0fVxuXG5cdFx0anNlLmxpYnMudGVtcGxhdGUubW9kYWwuZmluYWxpemVMYXllcigkd3JhcCwgb3JpZ2luYWxPcHRpb25zKTtcblxuXHRcdHJldHVybiBwcm9taXNlO1xuXHR9O1xuXG5cdHZhciBfY29udmVydFRlbXBsYXRlID0gZnVuY3Rpb24oa2V5LCB2YWx1ZSkge1xuXHRcdHZhciBuZXdWYWx1ZSA9IHtcblx0XHRcdHNyYzogdmFsdWUsXG5cdFx0XHR0eXBlOiAnaW5saW5lJ1xuXHRcdH07XG5cblx0XHRyZXR1cm4gWydpdGVtcycsIG5ld1ZhbHVlXTtcblx0fTtcblxuXHR2YXIgX2dldE1hcHBlciA9IGZ1bmN0aW9uKCkge1xuXHRcdHJldHVybiB7XG5cdFx0XHRkaWFsb2dDbGFzczogJ21haW5DbGFzcycsXG5cdFx0XHRtb2RhbDogZmFsc2UsXG5cdFx0XHRjbG9zZU9uRXNjYXBlOiAnZW5hYmxlRXNjYXBlS2V5Jyxcblx0XHRcdGNsb3NlT25PdXRlcjogJ2Nsb3NlT25CZ0NsaWNrJyxcblx0XHRcdGNsb3NlWDogJ3Nob3dDbG9zZUJ0bicsXG5cdFx0XHRzdG9yZVRlbXBsYXRlOiBmYWxzZSxcblx0XHRcdHRlbXBsYXRlOiBfY29udmVydFRlbXBsYXRlXG5cdFx0fTtcblx0fTtcblxuXHR2YXIgX3JlamVjdEhhbmRsZXIgPSBmdW5jdGlvbigkZWxlbWVudCwgZGVmZXJyZWQsIGdldEZvcm1EYXRhKSB7XG5cdFx0JGVsZW1lbnQgPSAkZWxlbWVudC5jbG9zZXN0KCcubWZwLXdyYXAnKTtcblx0XHRnZXRGb3JtRGF0YSgkZWxlbWVudCkuYWx3YXlzKGZ1bmN0aW9uKHJlc3VsdCkge1xuXHRcdFx0JGRvY3VtZW50Lm9mZigna2V5dXAubWFnbmlmaWMnKTtcblx0XHRcdGRlZmVycmVkLnJlamVjdChyZXN1bHQpO1xuXHRcdFx0JC5tYWduaWZpY1BvcHVwLmNsb3NlKCk7XG5cdFx0fSk7XG5cdH07XG5cblx0dmFyIF9yZXNvbHZlSGFuZGxlciA9IGZ1bmN0aW9uKCRlbGVtZW50LCBkZWZlcnJlZCwgZ2V0Rm9ybURhdGEpIHtcblx0XHQkZWxlbWVudCA9ICRlbGVtZW50LmNsb3Nlc3QoJy5tZnAtd3JhcCcpO1xuXHRcdGdldEZvcm1EYXRhKCRlbGVtZW50LCB0cnVlKS5kb25lKGZ1bmN0aW9uKHJlc3VsdCkge1xuXHRcdFx0JGRvY3VtZW50Lm9mZigna2V5dXAubWFnbmlmaWMnKTtcblx0XHRcdGRlZmVycmVkLnJlc29sdmUocmVzdWx0KTtcblx0XHRcdCQubWFnbmlmaWNQb3B1cC5jbG9zZSgpO1xuXHRcdH0pO1xuXHR9O1xuXG5cbi8vICMjIyMjIyMjIyMgVkFSSUFCTEUgRVhQT1JUICMjIyMjIyMjIyNcblxuXHRleHBvcnRzLm9wZW5MYXllciA9IF9vcGVuTGF5ZXI7XG5cdGV4cG9ydHMuZ2V0TWFwcGVyID0gX2dldE1hcHBlcjtcblx0ZXhwb3J0cy5nZXRSZXNvbHZlSGFuZGxlciA9IF9yZXNvbHZlSGFuZGxlcjtcblx0ZXhwb3J0cy5nZXRSZWplY3RIYW5kbGVyID0gX3JlamVjdEhhbmRsZXI7XG5cbn0oanNlLmxpYnMudGVtcGxhdGUubW9kYWwubWFnbmlmaWMpKTsiXX0=
<?php
/**
 * Shared UI component markup — included once at the bottom of every page via
 * footer.php / admin_footer.php. Provides:
 *   - Toast notification container (#toastContainer)
 *   - Image lightbox (#imageLightbox, supports zoom)
 *   - Modern confirm dialog (#confirmModal)
 *
 * Behaviour is wired up by include/theme.js (loaded alongside this file).
 */
?>
<!-- Toast notification container -->
<div id="toastContainer" class="toast-container" aria-live="polite" aria-atomic="true"></div>

<!-- Image lightbox -->
<div id="imageLightbox" class="lightbox" onclick="if(event.target===this)closeLightbox()">
    <button class="lightbox-close" onclick="closeLightbox()" type="button" aria-label="Close preview">
        <i class="fa-solid fa-xmark"></i>
    </button>
    <img id="lightboxImg" src="" alt="Preview">
    <div class="lb-caption" id="lightboxCaption"></div>
</div>

<!-- Modern confirm dialog (replaces native confirm()) -->
<div id="confirmModal" class="ui-modal-overlay" onclick="if(event.target===this)closeConfirm()">
    <div class="ui-modal p-6" role="dialog" aria-modal="true">
        <div class="flex items-start gap-3">
            <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-full bg-red-100 text-lg text-red-600">
                <i class="fa-solid fa-triangle-exclamation"></i>
            </div>
            <div class="flex-1">
                <h3 id="confirmTitle" class="text-lg font-bold text-slate-800">Are you sure?</h3>
                <p id="confirmMessage" class="mt-1 text-sm text-slate-500"></p>
            </div>
        </div>
        <div class="mt-6 flex justify-end gap-3">
            <button class="btn btn-ghost" type="button" onclick="closeConfirm()">Cancel</button>
            <button id="confirmOkBtn" class="btn btn-danger" type="button" onclick="runConfirm()">Confirm</button>
        </div>
    </div>
</div>

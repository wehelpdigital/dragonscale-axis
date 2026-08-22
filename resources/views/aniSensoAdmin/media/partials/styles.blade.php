{{-- What the four media screens share: a thumbnail that can be clicked open,
     and a viewer big enough to judge a picture by. --}}
<style>
    .media-thumb { width: 72px; height: 54px; object-fit: cover; border-radius: 6px;
        border: 1px solid #eff2f7; cursor: zoom-in; background: #f8f9fa; }
    .media-thumb.is-tall { height: 72px; }
    .media-gone { display: inline-flex; align-items: center; justify-content: center;
        width: 72px; height: 54px; border-radius: 6px; border: 1px dashed #d9dee7;
        background: #f8f9fa; color: #adb5bd; font-size: 18px; }
    .media-viewer img, .media-viewer video { max-width: 100%; max-height: 72vh; border-radius: 8px; }
    .media-viewer { text-align: center; }
    .media-caption { font-size: 12px; color: #74788d; margin-top: 8px; }
    .room-turn { display: flex; gap: 10px; padding: 8px 0; border-bottom: 1px solid #f1f3f7; }
    .room-turn:last-child { border-bottom: 0; }
    .room-who { font-weight: 600; color: #2a3042; font-size: 13px; }
    .room-body { font-size: 13px; color: #495057; white-space: pre-wrap; word-break: break-word; }
    .room-meta { font-size: 11px; color: #74788d; }
    .kind-chip { display: inline-block; font-size: 11px; font-weight: 600; color: #495057;
        background: #f1f3f7; border-radius: 999px; padding: 2px 8px; margin: 0 4px 4px 0; }
</style>

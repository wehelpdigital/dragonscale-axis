{{-- ===================== DARK =====================
     Bootstrap 5.3 brings its own dark mode and the switch in the right-hand
     bar already turns it on: `data-bs-theme="dark"` on <html>, which repaints
     the body, the cards and the chrome. What it cannot repaint is everything
     this page draws for itself — a board, its days, its cards, its chips and
     every sheet on top of them are written in hex, and hex does not know what
     mode it is in. Half the page went dark and the working half stayed white.

     So: one layer, in one place, that answers for all of it. Surfaces step
     down, borders step up, text steps up, and the colours that MEAN something
     — a priority, a type, a done tick — keep their hue and only lose their
     brightness, because a red that reads as danger on white has to still read
     as danger on black.

     Kept as its own file rather than a tail on the page's style block: it is
     the only thing here that is about the mode rather than about the module,
     and a reader looking for "why is that grey" should find one answer. --}}
<style>
[data-bs-theme="dark"] {
    /* The page's own ramp, as five names rather than thirty hexes. */
    --sm-surface: #2a3042;      /* a card, a chip, anything that sits up */
    --sm-surface-2: #262c3c;    /* a header strip inside one */
    --sm-sunken: #222736;       /* the page behind them */
    --sm-line: #39405a;         /* every border that was #e6e8ec */
    --sm-text: #cdd3e4;
    --sm-dim: #8b93ab;
}

/* ---- the module shelf and its drawers ---- */
[data-bs-theme="dark"] .sm-tabs .nav-link,
[data-bs-theme="dark"] .sm-subtabs .nav-link {
    background: var(--sm-surface); border-color: var(--sm-line); color: var(--sm-text);
}
[data-bs-theme="dark"] .sm-tabs .nav-link:hover,
[data-bs-theme="dark"] .sm-subtabs .nav-link:hover {
    background: #333b52; border-color: #4a5474; color: #fff;
}
[data-bs-theme="dark"] .sm-tabs .nav-link.active,
[data-bs-theme="dark"] .sm-subtabs .nav-link.active { background: #556ee6; border-color: #556ee6; color: #fff; }
[data-bs-theme="dark"] .sm-tabs .nav-link .badge,
[data-bs-theme="dark"] .sm-subtabs .nav-link .badge { background: #3a4258 !important; color: var(--sm-text) !important; }

/* ---- the board ---- */
[data-bs-theme="dark"] .date-activities { background: var(--sm-surface); border-color: var(--sm-line); }
[data-bs-theme="dark"] .activity-card { background: var(--sm-surface); color: var(--sm-text); }
[data-bs-theme="dark"] .activity-card:hover { background: #303751; }
[data-bs-theme="dark"] .activity-card.is-hidden { background: var(--sm-surface-2); }
[data-bs-theme="dark"] .activity-card + .activity-card { border-top-color: var(--sm-line); }
[data-bs-theme="dark"] .activity-card .step-meta,
[data-bs-theme="dark"] .activity-card .step-meta i { color: var(--sm-dim); }
/* A description is body copy the client wrote; it has to READ. */
[data-bs-theme="dark"] .activity-description-content,
[data-bs-theme="dark"] .activity-description-content p,
[data-bs-theme="dark"] .activity-description-content li { color: var(--sm-text); }
[data-bs-theme="dark"] .activity-description-content h1,
[data-bs-theme="dark"] .activity-description-content h2,
[data-bs-theme="dark"] .activity-description-content h3,
[data-bs-theme="dark"] .activity-description-content h4 { color: #eef1f8; }
[data-bs-theme="dark"] .activity-description-content table td,
[data-bs-theme="dark"] .activity-description-content table th { border-color: var(--sm-line); }
/* A note pinned to a day was a sheet of cream paper. */
[data-bs-theme="dark"] .date-note-block { background: #2e2a1f; border-color: #4a422c; color: #e8dfc7; }
[data-bs-theme="dark"] .date-note-block .text-secondary { color: #b9ad8c !important; }

/* A day with nothing on it was a pale card standing among dark ones —
   the one thing on the board that still looked like paper. */
[data-bs-theme="dark"] .rest-day-marker {
    background: linear-gradient(180deg, #2a3042 0%, #262c3c 100%);
    border-color: var(--sm-line); border-left-color: #454d68;
}
[data-bs-theme="dark"] .rest-day-marker:hover { border-left-color: #6d84ee; }
[data-bs-theme="dark"] .rest-day-marker .rest-day-date { color: var(--sm-text); }
[data-bs-theme="dark"] .rest-day-marker .rest-day-icon,
[data-bs-theme="dark"] .rest-day-marker .rest-day-tag { color: var(--sm-dim); }

/* ---- the small labels ---- */
/* The card renderer paints a lot tag and a worker tag inline, and an inline
   style outranks any rule that does not insist. Insisting here is cheaper
   and safer than reaching into the renderer to take the colour out of it. */
[data-bs-theme="dark"] .item-tag { background: #313a5e !important; color: #b9c4f0 !important; }
[data-bs-theme="dark"] .item-tag[data-lot-id] { background: #2f3a63 !important; color: #b9c4f0 !important; }
[data-bs-theme="dark"] .item-tag.service { background: #1e3b31; color: #7fd3b0; }
[data-bs-theme="dark"] .item-tag.custom { background: #3b3220 !important; color: #e0b877 !important; }
[data-bs-theme="dark"] .item-tag.service { background: #1e3b31 !important; color: #7fd3b0 !important; }
[data-bs-theme="dark"] .activity-na-tag { background: #333b52; color: var(--sm-dim); }
[data-bs-theme="dark"] .lot-chip { background: var(--sm-surface); border-color: var(--sm-line); color: var(--sm-text); }
[data-bs-theme="dark"] .lot-chip.active,
[data-bs-theme="dark"] .lot-chip[aria-pressed="true"] { background: #556ee6; border-color: #556ee6; color: #fff; }
/* Priorities keep their hue and lose their glare: a red that reads as
   danger on white has to still read as danger on black. */
[data-bs-theme="dark"] .priority-medium { background: #b8862f; color: #1a1a1a; }
[data-bs-theme="dark"] .priority-low { background: #4b5265; color: #dfe3ee; }

/* ---- what the board shows, and the mirror ---- */
[data-bs-theme="dark"] .vf-row { background: var(--sm-surface); border-color: var(--sm-line); }
[data-bs-theme="dark"] .vf-row:hover { background: #333b52; border-color: #4a5474; }
[data-bs-theme="dark"] .vf-ico { background: #333b52; color: #9fb0f5; }
[data-bs-theme="dark"] .vf-txt b { color: #eef1f8; }
[data-bs-theme="dark"] .vf-txt i { color: var(--sm-dim); }
[data-bs-theme="dark"] .vf-state { background: #1e3b31; color: #7fd3b0; }
[data-bs-theme="dark"] .vf-state.is-off { background: #333b52; color: var(--sm-dim); }
[data-bs-theme="dark"] .vf-go { background: #313a5e; color: #b9c4f0; }
[data-bs-theme="dark"] .mir-day { border-color: var(--sm-line); }
[data-bs-theme="dark"] .mir-dayhead { background: var(--sm-surface-2); color: var(--sm-text); }
[data-bs-theme="dark"] .mir-act { border-top-color: var(--sm-line); color: var(--sm-text); }
[data-bs-theme="dark"] .mir-act.is-done { color: var(--sm-dim); }
[data-bs-theme="dark"] .mir-act.is-hidden { background: var(--sm-surface-2); }
[data-bs-theme="dark"] .mir-meta { color: var(--sm-dim); }
[data-bs-theme="dark"] .mir-flag { background: #333b52; color: var(--sm-dim); }

/* ---- documentation, attachments, rules ---- */
[data-bs-theme="dark"] .attachment-card,
[data-bs-theme="dark"] .critical-rule-row { background: var(--sm-surface); border-color: var(--sm-line); color: var(--sm-text); }
[data-bs-theme="dark"] .attachment-card:hover,
[data-bs-theme="dark"] .critical-rule-row:hover { background: #303751; }
[data-bs-theme="dark"] .attachment-thumb,
[data-bs-theme="dark"] .attachment-file-icon { background: var(--sm-surface-2); }
[data-bs-theme="dark"] .attachment-description,
[data-bs-theme="dark"] .attachment-filename { color: var(--sm-text); }
[data-bs-theme="dark"] .doc-subtabs .nav-link { background: var(--sm-surface); border-color: var(--sm-line); color: var(--sm-text); }
[data-bs-theme="dark"] .doc-subtabs .nav-link.active { background: #556ee6; border-color: #556ee6; color: #fff; }
[data-bs-theme="dark"] .sm-quill-wrap { background: var(--sm-surface); }

/* ---- every module tab this page grew ---- */
[data-bs-theme="dark"] .nt-card, [data-bs-theme="dark"] .gl-tile, [data-bs-theme="dark"] .ph-card,
[data-bs-theme="dark"] .iv-item, [data-bs-theme="dark"] .mp-card, [data-bs-theme="dark"] .dw-tile,
[data-bs-theme="dark"] .ai-row, [data-bs-theme="dark"] .cb-panel, [data-bs-theme="dark"] .rp-card,
[data-bs-theme="dark"] .rp-link, [data-bs-theme="dark"] .de-card, [data-bs-theme="dark"] .cf-fig,
[data-bs-theme="dark"] .gl-albums .gl-chip, [data-bs-theme="dark"] .de-tag, [data-bs-theme="dark"] .de-file {
    background: var(--sm-surface); border-color: var(--sm-line); color: var(--sm-text);
}
[data-bs-theme="dark"] .nt-card:hover, [data-bs-theme="dark"] .ph-card:hover,
[data-bs-theme="dark"] .iv-item:hover, [data-bs-theme="dark"] .mp-card:hover,
[data-bs-theme="dark"] .ai-row:hover, [data-bs-theme="dark"] .de-card:hover,
[data-bs-theme="dark"] .rp-link:hover { background: #303751; border-color: #4a5474; }
[data-bs-theme="dark"] .nt-title, [data-bs-theme="dark"] .ph-title, [data-bs-theme="dark"] .iv-name,
[data-bs-theme="dark"] .mp-title, [data-bs-theme="dark"] .dw-title, [data-bs-theme="dark"] .ai-row-t,
[data-bs-theme="dark"] .rp-title, [data-bs-theme="dark"] .de-title, [data-bs-theme="dark"] .gl-cap,
[data-bs-theme="dark"] .cb-body, [data-bs-theme="dark"] .dc-what { color: #eef1f8; }
[data-bs-theme="dark"] .nt-meta, [data-bs-theme="dark"] .nt-words, [data-bs-theme="dark"] .ph-meta,
[data-bs-theme="dark"] .iv-kind, [data-bs-theme="dark"] .mp-meta, [data-bs-theme="dark"] .mp-labels,
[data-bs-theme="dark"] .dw-meta, [data-bs-theme="dark"] .ai-row-m, [data-bs-theme="dark"] .gl-meta,
[data-bs-theme="dark"] .rp-meta, [data-bs-theme="dark"] .de-meta, [data-bs-theme="dark"] .de-words,
[data-bs-theme="dark"] .cb-at, [data-bs-theme="dark"] .cf-fig span { color: var(--sm-dim); }
[data-bs-theme="dark"] .nt-shelf, [data-bs-theme="dark"] .ph-fig, [data-bs-theme="dark"] .mp-kind,
[data-bs-theme="dark"] .ai-kind, [data-bs-theme="dark"] .rp-fig, [data-bs-theme="dark"] .de-type,
[data-bs-theme="dark"] .cb-page { background: #313a5e; color: #b9c4f0; }
[data-bs-theme="dark"] .nt-shelf.is-date, [data-bs-theme="dark"] .de-type { background: #3b3220; color: #e0b877; }
[data-bs-theme="dark"] .nt-shelf.is-inline, [data-bs-theme="dark"] .ai-kind.is-team,
[data-bs-theme="dark"] .rp-fig.is-good { background: #1e3b31; color: #7fd3b0; }
[data-bs-theme="dark"] .de-type.is-rule, [data-bs-theme="dark"] .rp-fig.is-bad { background: #3d2224; color: #e8a0a4; }
[data-bs-theme="dark"] .iv-item.is-low { background: #33301f; border-color: #5c5230; }
[data-bs-theme="dark"] .iv-low { color: #e0b877; }
[data-bs-theme="dark"] .iv-move, [data-bs-theme="dark"] .cb-msg, [data-bs-theme="dark"] .cb-rec,
[data-bs-theme="dark"] .dc-row { border-top-color: var(--sm-line); border-bottom-color: var(--sm-line); }
[data-bs-theme="dark"] .gl-gone, [data-bs-theme="dark"] .dw-gone { background: var(--sm-surface-2); color: #4e566b; }
[data-bs-theme="dark"] .ai-bubble { background: var(--sm-surface-2); color: var(--sm-text); }
[data-bs-theme="dark"] .ai-turn.is-bot .ai-bubble { background: #313a5e; color: #cdd8ff; }
[data-bs-theme="dark"] .nt-empty, [data-bs-theme="dark"] .gl-empty, [data-bs-theme="dark"] .ph-empty,
[data-bs-theme="dark"] .iv-empty, [data-bs-theme="dark"] .mp-empty, [data-bs-theme="dark"] .dw-empty,
[data-bs-theme="dark"] .ai-empty, [data-bs-theme="dark"] .cb-empty, [data-bs-theme="dark"] .rp-empty,
[data-bs-theme="dark"] .de-empty, [data-bs-theme="dark"] .cf-empty { color: var(--sm-dim); }

/* ---- the community's shelf and its screens ---- */
[data-bs-theme="dark"] .cm-shelf a { background: var(--sm-surface); border-color: var(--sm-line); color: var(--sm-text); }
[data-bs-theme="dark"] .cm-shelf a:hover { background: #333b52; border-color: #4a5474; color: #fff; }
[data-bs-theme="dark"] .cm-shelf a.is-on { background: #556ee6; border-color: #556ee6; color: #fff; }
[data-bs-theme="dark"] .cm-shelf .badge { background: #3a4258; color: var(--sm-text); }
[data-bs-theme="dark"] .cf-name { color: var(--sm-text); }
[data-bs-theme="dark"] .cf-arrow { color: var(--sm-dim); }
[data-bs-theme="dark"] .cf-state.is-accepted { background: #1e3b31; color: #7fd3b0; }
[data-bs-theme="dark"] .cf-state.is-pending { background: #3b3220; color: #e0b877; }
[data-bs-theme="dark"] .cf-state.is-other { background: #333b52; color: var(--sm-dim); }

/* ---- the article builder ---- */
[data-bs-theme="dark"] .bb-block { background: var(--sm-surface); border-color: var(--sm-line); }
[data-bs-theme="dark"] .bb-head { background: var(--sm-surface-2); border-bottom-color: var(--sm-line); }
[data-bs-theme="dark"] .bb-kind { color: #9fb0f5; }
[data-bs-theme="dark"] .bb-grip { color: var(--sm-dim); }
[data-bs-theme="dark"] .bb-body label { color: var(--sm-dim); }
[data-bs-theme="dark"] .bb-add button { background: #262c3c; border-color: #4a5474; color: #b9c4f0; }
[data-bs-theme="dark"] .bb-add button:hover { background: #313a5e; }
[data-bs-theme="dark"] .bb-empty { border-color: var(--sm-line); color: var(--sm-dim); }
[data-bs-theme="dark"] .bb-thumbs img { border-color: var(--sm-line); }
[data-bs-theme="dark"] .bb-link { color: var(--sm-dim); }
/* The frame holds another app's page, which brings its own mode. A white
   sheet behind it while it loads is the only thing this side owes it. */
[data-bs-theme="dark"] .bb-frame { border-color: var(--sm-line); background: var(--sm-surface-2); }

/* ---- the odd Bootstrap helper Skote leaves light ---- */
/* An outline-secondary button on a dark page is a grey line on a grey field.
   It is the whole toolbar above the board, so it has to be readable. */
[data-bs-theme="dark"] .btn-outline-secondary {
    color: var(--sm-text); border-color: #4a5474;
}
[data-bs-theme="dark"] .btn-outline-secondary:hover {
    background: #333b52; border-color: #6d84ee; color: #fff;
}
[data-bs-theme="dark"] .bg-light { background-color: var(--sm-surface-2) !important; }
[data-bs-theme="dark"] .text-dark { color: var(--sm-text) !important; }
[data-bs-theme="dark"] .table-light > :not(caption) > * > * { background-color: var(--sm-surface-2); color: var(--sm-text); }
</style>

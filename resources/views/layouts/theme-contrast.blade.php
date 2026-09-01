{{-- ===================== READABLE IN EITHER MODE =====================

     Measured, not guessed. Every visible run of text on thirty AniSenso pages
     was walked in the dark and its contrast computed against the background it
     is actually painted on. What follows is the list of things that came back
     under the line, and nothing else.

     Two sections. The first is about colours that read badly in BOTH modes —
     mostly the framework's light fills with white written on them, which are
     2.2:1 whichever way the switch is set. The second is dark mode only, where
     Bootstrap 5.3 repaints the chrome and leaves everything the theme wrote in
     hex exactly as it was.

     Loaded after app.min.css so it wins on order rather than on !important,
     which is also why almost none of it needs !important. --}}
<style>
/* ============ reads badly in either mode ============
   A filled badge is a light colour with white written on it: the amber
   #f1b44c, the green #34c38f, the blue #50a5f1, the orange #fd7e14. That is
   2.2 to 2.6 against the fill — under half of what a person needs to read a
   word. The fills do not move, because a success badge is still that green and
   people know it by its colour. Only the ink changes, to a deep version of the
   same hue. */
.badge.bg-success { color: #06331f !important; }
.badge.bg-info    { color: #062b4a !important; }
.badge.bg-warning { color: #453100 !important; }
.badge.bg-orange  { color: #401d00 !important; }
.badge.bg-danger  { color: #4a0808 !important; }
/* !important is not decoration here. Bootstrap writes .text-white and
   .text-dark with it, so `bg-warning text-dark` — a common pairing, and the
   right one on a white page — keeps near-white ink in the dark and lands on
   amber at 1.71:1. The ink cannot win the argument politely. */
.badge.bg-success.text-white,
.badge.bg-info.text-white,
.badge.bg-warning.text-white,
.badge.bg-orange.text-white,
.badge.bg-success.text-dark,
.badge.bg-info.text-dark,
.badge.bg-warning.text-dark,
.badge.bg-orange.text-dark { color: inherit; }
.badge.bg-success.text-white, .badge.bg-success.text-dark { color: #06331f !important; }
.badge.bg-info.text-white,    .badge.bg-info.text-dark    { color: #062b4a !important; }
.badge.bg-warning.text-white, .badge.bg-warning.text-dark { color: #453100 !important; }
.badge.bg-orange.text-white,  .badge.bg-orange.text-dark  { color: #401d00 !important; }
.badge.bg-danger.text-white,  .badge.bg-danger.text-dark  { color: #4a0808 !important; }

/* The brand blue wherever white is written on it, so the button, the badge and
   the selected pill agree with one another. */
.badge.bg-primary { background-color: #4c65e0 !important; }
.bg-primary { background-color: #4c65e0 !important; }
.btn-secondary { --bs-btn-bg: #656a7d; --bs-btn-border-color: #656a7d;
    --bs-btn-hover-bg: #565b6c; --bs-btn-hover-border-color: #4f5464; }
.nav-pills .nav-link.active,
.nav-pills .show > .nav-link { background-color: #4c65e0; }

/* THE PRIMARY BUTTON, which is on nearly every page and is the most-pressed
   thing in the admin. White on #556ee6 is 4.41:1 — under the line by less than
   a tenth, which is exactly the kind of miss that never gets fixed because it
   never looks broken. The blue steps down by a shade nobody will notice and
   the label passes. */
.btn-primary { --bs-btn-bg: #4c65e0; --bs-btn-border-color: #4c65e0;
    --bs-btn-hover-bg: #4053c4; --bs-btn-hover-border-color: #3c4eb9;
    --bs-btn-active-bg: #3c4eb9; --bs-btn-active-border-color: #3849ac;
    --bs-btn-disabled-bg: #4c65e0; --bs-btn-disabled-border-color: #4c65e0; }

/* The grey badge is the one case where white is right and the fill is simply
   too light: #74788d gives 4.36:1, a hair under. The fill steps down instead
   of the ink stepping up, because grey has no deep version that still reads as
   "nothing special". */
.badge.bg-secondary { background-color: #656a7d !important; }

/* The sidebar is dark in BOTH modes — it is set that way by the theme, not by
   the switch — so its labels at 3.43:1 have always been hard to read, in the
   light as much as in the dark. This is the one rule here that is not about
   dark mode at all. */
#sidebar-menu ul li a,
#sidebar-menu ul li a i,
#sidebar-menu ul li a span { color: #aab3c9; }
#sidebar-menu ul li.mm-active > a,
#sidebar-menu ul li a:hover { color: #ffffff; }

/* ============ dark mode ============
   Bootstrap 5.3's dark mode repaints the body, the cards and the chrome. What
   it cannot repaint is everything written in hex by the theme or by a page,
   and hex does not know what mode it is in. */

/* SECONDARY TEXT — the single most common failure, on every page that has a
   subtitle: #74788d on a #2a3042 card is 3.01:1. It is meant to be quiet, not
   inaudible. */
[data-bs-theme="dark"] .text-secondary,
[data-bs-theme="dark"] .text-muted,
[data-bs-theme="dark"] small.text-secondary,
[data-bs-theme="dark"] td.text-secondary,
[data-bs-theme="dark"] .table > :not(caption) > * > .text-secondary { color: #9aa2ba !important; }

/* OUTLINE BUTTONS — an outline button's label IS its colour, and all four of
   them are dark inks on what is now a dark card. Bootstrap 5.3 reads these
   from --bs-btn-* variables; setting `color` does nothing. */
[data-bs-theme="dark"] .btn.btn-outline-primary   { --bs-btn-color: #9fb0f5; --bs-btn-disabled-color: #5f6b9c; }
/* General again. It was scoped last pass because the chat-support panels were
   hard white in the dark; those panels now follow the mode, so the reason is
   gone and the narrower rule was simply missing buttons. */
[data-bs-theme="dark"] .btn.btn-outline-secondary { --bs-btn-color: #a8b0c4; --bs-btn-disabled-color: #6b7183; }
[data-bs-theme="dark"] .btn.btn-outline-dark      { --bs-btn-color: #b9c0cf; --bs-btn-disabled-color: #767c8c;
                                                    --bs-btn-hover-bg: #454c66; --bs-btn-hover-border-color: #454c66; }
[data-bs-theme="dark"] .btn.btn-outline-light     { --bs-btn-color: #cdd3e4; --bs-btn-hover-color: #222736; }
[data-bs-theme="dark"] .btn.btn-outline-info      { --bs-btn-color: #7cc0f5; --bs-btn-disabled-color: #4d7595; }
[data-bs-theme="dark"] .btn.btn-outline-success   { --bs-btn-color: #6ed6ae; --bs-btn-disabled-color: #47836c; }
[data-bs-theme="dark"] .btn.btn-outline-warning   { --bs-btn-color: #f0c274; --bs-btn-disabled-color: #96793f; }
[data-bs-theme="dark"] .btn.btn-outline-danger    { --bs-btn-color: #f59b9b; --bs-btn-disabled-color: #915a5a; }

/* TABS — an unselected tab kept its light-mode ink, #343a40 on #2a3042, which
   is 1.14:1: not "hard to read" but genuinely invisible. A row of tabs where
   only the open one can be seen reads as a page with no tabs. */
[data-bs-theme="dark"] .nav-tabs .nav-link:not(.active),
[data-bs-theme="dark"] .nav-pills .nav-link:not(.active),
[data-bs-theme="dark"] .nav-link:not(.active) { color: #b0b7c8; }
/* The selected one is white on the brand blue and was already right. Catching
   it in the rule above put pale grey on blue: 2.20:1, worse than the problem
   being fixed. */
[data-bs-theme="dark"] .nav-pills .nav-link.active { color: #ffffff; }
[data-bs-theme="dark"] .nav-tabs .nav-link:hover { color: #e5e9f3; }
[data-bs-theme="dark"] .nav-tabs .nav-link.active { color: #cdd3e4; background-color: #2a3042; border-color: #39405a #39405a #2a3042; }
[data-bs-theme="dark"] .nav-tabs { border-color: #39405a; }

/* PAGINATION — every list that has more than one page. The links kept a white
   background in the dark, so a pale grey label sat on white inside a dark
   card. */
[data-bs-theme="dark"] .page-link { background-color: #2a3042; border-color: #39405a; color: #cdd3e4; }
[data-bs-theme="dark"] .page-link:hover { background-color: #343b52; color: #ffffff; }
/* Disabled still has to be readable — "Previous" on the first page is a word
   somebody reads to know where they are, not decoration. */
[data-bs-theme="dark"] .page-item.disabled .page-link { background-color: #262c3c; color: #8f97ad; }
[data-bs-theme="dark"] .page-item.active .page-link { background-color: #556ee6; border-color: #556ee6; color: #ffffff; }
[data-bs-theme="dark"] .dataTables_wrapper .dataTables_paginate .paginate_button { color: #cdd3e4 !important; }
[data-bs-theme="dark"] .dataTables_wrapper .dataTables_info,
[data-bs-theme="dark"] .dataTables_wrapper .dataTables_length,
[data-bs-theme="dark"] .dataTables_wrapper .dataTables_filter { color: #9aa2ba; }

/* WHITE SURFACES A PAGE PAINTED FOR ITSELF.
   `.text-dark` becomes near-white in dark mode, which is right — unless the
   thing behind it is a background hard-coded to white, in which case it is
   white on white at 1.08:1. Rather than hunt every page, the surfaces
   themselves go dark: a panel that asked to be white asked for "the card
   colour", and in the dark that is not white. */
[data-bs-theme="dark"] .bg-white { background-color: #2a3042 !important; }
[data-bs-theme="dark"] .bg-light { background-color: #262c3c !important; }
[data-bs-theme="dark"] .table-light,
[data-bs-theme="dark"] .table-light > th,
[data-bs-theme="dark"] .table-light > td { background-color: #262c3c; color: #cdd3e4; }

/* THE TOP BAR. The signed-in name at 2.08:1 was the worst thing measured on
   any page that was not literally invisible. */
[data-bs-theme="dark"] .header-item,
[data-bs-theme="dark"] #page-header-user-dropdown span,
[data-bs-theme="dark"] .navbar-header .dropdown-toggle { color: #cdd3e4; }

/* THE FOOTER and the settings drawer, both of which stayed light. */
[data-bs-theme="dark"] .footer { background-color: #262c3c; color: #9aa2ba; }
[data-bs-theme="dark"] .right-bar { background-color: #2a3042; color: #cdd3e4; }
[data-bs-theme="dark"] .right-bar .text-dark,
[data-bs-theme="dark"] .right-bar h5,
[data-bs-theme="dark"] .right-bar h6 { color: #cdd3e4 !important; }

/* A PLAIN LINK inside a dark card kept the brand blue it wears on white,
   which is 2.97:1 there. Buttons were given this treatment; ordinary links in
   body text were not, and they are the ones people actually have to find. */
[data-bs-theme="dark"] .card a:not(.btn):not(.page-link):not(.nav-link):not(.dropdown-item),
[data-bs-theme="dark"] .modal a:not(.btn):not(.page-link):not(.nav-link):not(.dropdown-item) { color: #9fb0f5; }

/* Inline code, which the theme prints in a pink meant for a white page. */
[data-bs-theme="dark"] code:not([class*="language-"]) { color: #ff9dcb; }

/* Form controls and their labels, which a few pages set by hand. */
[data-bs-theme="dark"] .form-control,
[data-bs-theme="dark"] .form-select,
[data-bs-theme="dark"] .input-group-text { background-color: #262c3c; border-color: #39405a; color: #cdd3e4; }
[data-bs-theme="dark"] .form-control::placeholder { color: #757c92; }
[data-bs-theme="dark"] .form-label,
[data-bs-theme="dark"] .form-label.text-dark { color: #cdd3e4; }

/* Modals — the one surface people look at most closely, and the one most
   often given a white background by hand. */
[data-bs-theme="dark"] .modal-content { background-color: #2a3042; color: #cdd3e4; }
[data-bs-theme="dark"] .modal-header,
[data-bs-theme="dark"] .modal-footer { border-color: #39405a; }
[data-bs-theme="dark"] .modal-title,
[data-bs-theme="dark"] .modal-content .text-dark { color: #e5e9f3 !important; }
[data-bs-theme="dark"] .btn-close { filter: invert(1) grayscale(100%) brightness(200%); }
</style>

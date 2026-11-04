# GTNHS Guidance Office — Design System (MASTER)

Source of truth for every screen. A page-specific file in `pages/<page>.md` overrides this file for that page only.
Visual reference: the "GTNHS Guidance — UI Design" canvas (Login, Today's desk, Student record, Case file, Encode referral, Admin, Foundations).

## Concept: the index-card system

The UI is built from the objects the Guidance Office already uses:

| Paper object | UI element |
| --- | --- |
| Ruled index card + red margin line | Student identity header, sign-in card, written notes |
| Manila folder with typed tab | Guidance case header (`GC-2026-0042` on the tab) |
| Filing-cabinet index codes | Navigation codes (`ST`, `AP`, `RF`, `GC`, `CS`, `FU`, `DC`, `PG`, `RP`) and record-number prefixes |
| Rubber stamps | Outlined status labels |
| Signed ledger entry | "— signed J. Ramos" on every record or history entry (`created_by` / `updated_by`) |
| Sealed envelope | Counseling notes hidden until the user unseals them (logged) |

## Color tokens: the field palette (Tailwind 4 `@theme`)

Taken from the GTNHS seal and softened so it stays warm and easy on the eyes all day. The seal's bright green and yellow are used only as small decorative details, never for text or large areas.

```css
@theme {
  --color-ground: #F4F1E8;     /* page background, warm parchment */
  --color-surface: #FFFDF7;    /* cards, "index card" */
  --color-sunk: #EEEADD;       /* hover rows, inset areas */
  --color-ink: #1C2A22;        /* primary text, green-black */
  --color-ink-2: #5A4D45;      /* secondary text, warm brown (fence/silo) */
  --color-rule: #E2DCCB;       /* hairline borders */
  --color-rule-2: #8C826F;     /* input borders (3:1 non-text) */
  --color-green: #1E5E34;      /* primary action, links, focus, in progress (seal's dark ring) */
  --color-green-soft: #E4EFE3;
  --color-sprout: #4CB85A;     /* seal's bright ring: decoration only */
  --color-gold: #E3B93A;       /* rice stalk: decoration only */
  --color-ochre: #7A5600;      /* gold as text: waiting / pending */
  --color-ochre-soft: #F6EBC8;
  --color-filed: #7A4A2A;      /* done / completed ("filed away") */
  --color-filed-soft: #F1E6DA;
  --color-brick: #A8321E;      /* confidential, overdue, destructive */
  --color-brick-soft: #F7E4DC;
  --color-folder: #E9DDBE;     /* case-file header only */
  --color-folder-ink: #3D3220;
  --color-card-rule: #DCE6D6;  /* ruled lines on index cards, pale green */
  --color-margin: #C9573F;     /* index-card margin line (decorative) */
  --color-band: #17442A;       /* sign-in panel, deep school green */

  --font-sans: "Public Sans", system-ui, sans-serif;
  --font-mono: "IBM Plex Mono", ui-monospace, monospace;
  --font-display: "Newsreader", Georgia, serif;

  --radius-sm: 2px;  /* stamps, codes */
  --radius: 3px;     /* cards, inputs, buttons */
  --radius-tab: 6px; /* folder tabs only */
}
```

Dark mode (warm night): ground `#141A16`, surface `#1C241F`, ink `#ECE8DC`, ink-2 `#BDB4A6`, rule `#2F3A33`, green `#7CC48A`, ochre `#E3C15A`, filed `#D9A77E`, brick `#F0957F`, folder `#3A3324`, band `#0F2618`.

Admin workspace: same tokens, but the sidebar is a dark green-black band (`#1F2A24`), so the two roles never look alike.

All text pairs meet 4.5:1 (green on cream ≈ 7.8:1, brown ≈ 8:1, ochre ≈ 6.5:1, brick ≈ 6.3:1). Every status has a written label, so color is never the only signal.

The original cool "ink" palette (blue-black, pen blue on sage grey) was the first draft and stays available on the canvas for comparison only.

## Logo

Use the GTNHS seal in the sidebar header (40px, circular crop) and on the sign-in panel (64px, with a 3px sprout ring). Don't place it on busy backgrounds or stretch it, and don't use it as a watermark.

## Typography

| Role | Font | Size / weight |
| --- | --- | --- |
| Page title, student name | Newsreader | 38–48px / 500. Use nowhere else. |
| Section heading | Public Sans | 24px / 700 |
| Card heading | Public Sans | 16px / 700 |
| Body | Public Sans | 15px / 400, line-height 1.5 (inputs 15–16px) |
| Helper text | Public Sans | 13px, ink-2 |
| IDs, dates, times, codes | IBM Plex Mono | 12–18px |
| Section labels | IBM Plex Mono | 11px uppercase, +0.1em tracking, ink-2 |

## Status stamps (outlined, uppercase mono, 1.5px border, 2px radius)

- **green** (in progress): Scheduled, Ongoing, Open, Under review
- **ochre** (waiting): Received, Rescheduled, On hold, Pending
- **filed brown** (done): Completed, Active, Enrolled, Verified
- **brick** (urgent): Overdue, Confidential
- **ink-2** (ended): Closed, Cancelled, No-show, Deactivated

## Components

- **Navigation rail**: grouped as Desk / Records / Casework, with a code chip per item. The active item has a filled ink chip and a surface background. Admin uses a dark ink rail, so the two roles never look alike.
- **Ledger row**: replaces stat tiles. Counts sit in one ruled row (mono numerals plus a short label), and each count links to its list.
- **Docket**: today's appointments on hour-ruled rows with a red "now" line.
- **Case history**: a timeline of entries. Each entry has a mono timestamp, a title, a stamp and a signature line.
- **Sealed notes**: a dashed stamp-red box with a lock, a short warning and an "Unseal notes" button. Unsealing writes an `UNSEAL` audit event, and the notes seal again when the user leaves the page.
- **Masked LRN**: shows `•••• •••• 0214` with a Show/Hide toggle.
- **Forms**: visible labels, a red `*` for required fields, helper text under the field, and errors right below the field (`aria-invalid` + `aria-describedby`). Fields are grouped into numbered mono legends (`01 · STUDENT`).
- **Buttons**: at least 44px tall. Only one green primary per view. Quiet buttons are outlined. Brick red is only for removing access.
- **Sample banner**: every non-production screen shows an ochre strip reading "SAMPLE RECORDS · SYNTHETIC DATA".

## Rules (what keeps it from looking generic)

1. Hairline borders and flat surfaces. No drop shadows, gradients, glass or glows.
2. Corners 2–3px; only folder tabs are 6px.
3. No icon-in-a-circle stat cards and no "Welcome back" hero.
4. Icons are inline stroke SVG, used sparingly, and `aria-hidden` when decorative. No emoji.
5. Plain office wording: "Encode referral", "Signed", "Unseal notes", "Needs attention".
6. Focus ring: 2px green outline, 2px offset, never removed.
7. Responsive: the rail stacks above the content on phones, wide tables scroll inside their own box, and the page never scrolls sideways.
8. Reports show totals only. Never put names or note contents in charts.

## Open items (confirm with the Guidance Department)

Referral and case categories, the session-note fields, the student fields to collect, whether priority levels are used, and the document types allowed.

# Content plan and legacy material

## Legacy PDF (six English articles)

| Article | Destination | Treatment |
|---|---|---|
| Understanding Lailatul Qadar | What Is Laylat al-Qadr? | Merge and rewrite |
| Deciphering the Signs | Signs of Laylat al-Qadr | Keep structure; rewrite with verified hadith |
| The Religious Essence | What Is Laylat al-Qadr? | Merge; keep the thousand-months calculation |
| Seeking Forgiveness | Laylat al-Qadr Duʿāʾ | Rewrite around the ʿafw supplication |
| Experience the Blessings | What Is Laylat al-Qadr | Merge; drop unsourced claims |
| Embracing the Spiritual Essence | None | Retire |

## Articles shipped

| Page | Words | Footnotes | Status |
|---|---|---|---|
| Laylat al-Qadr Duʿāʾ | 1,333 | 6 | Published by setup |
| Sūrat al-Qadr | 1,429 | 4 | Published by setup |
| When Is Laylat al-Qadr? | 1,317 | 10 | Published by setup |
| The Last Ten Nights of Ramadan | 1,268 | 8 | Published by setup |
| Signs of Laylat al-Qadr | 1,240 | 5 | Published by setup |
| Start Here | about 720 | 3 | Published by setup |
| What Is Laylat al-Qadr? | about 2,050 | 15 | Published by setup |
| Prayer and Worship on Laylat al-Qadr | about 2,470 | 22 | Published by setup |
| Frequently Asked Questions | about 2,200 | 20 | Published by setup |
| Hadith and Sources | about 2,700 | 34 | Published by setup |
| The Islamic Dilemma and the Qurʾān as Guardian (1.9.5) | about 2,070 | 7 | Published by setup; Malay draft |

Word counts exclude Arabic text, illustrations and footnotes.

## Publishing and updates

Setup (on activation, on each theme update, and from Tools > Run setup) writes every shipped article into its page and publishes it. Pages without a shipped article stay drafts. When an article in `content/` changes in a release:

- a page nobody has edited takes the new text and footnotes, and WordPress keeps the old text as a revision;
- a page an editor has changed is left as it is and listed on the Tools tab under "Edited, not updated", with a button to replace it;
- obsolete copies the theme created earlier (empty or unedited drafts, or a page renamed to `slug-2`) go to the trash; pages the theme did not create are never touched.

"Unedited" means the content and footnotes still match the fingerprint stored at seeding (`_lq_seed_hash`). Pages seeded before 1.9.4 have no fingerprint; they count as unedited when their text equals any version shipped earlier, listed in `inc/data/seed-history.json`. When you change an article, add the md5 of its previous `content` (trimmed, `\n` line endings) to that file, or rebuild the file from `git log -p content/`.

## Standards

- Every hadith carries its collection, number and grading, verified live before publication.
- One display spelling per language; no rotation of variants in body copy.
- Malay copy follows DBP standards; each Malay page is a translation of its English counterpart and is paired with it by Run setup.

# Night window

The night window block shows, for one city, the ten nights from the 21st to the 30th of Ramadan: sunset (the night begins), the start of the last third, and the start of Fajr (the night ends). The last third is the span from sunset to Fajr divided by three. The 30th night is marked as conditional, since it occurs only in a thirty-day month.

## Data flow

- `inc/night-window.php` builds the configuration: cities, interface strings for the current language, and the two start dates from the Ramadan Dates tab. The filter `lq_night_window_config` can change any of it.
- `blocks/night-window/render.php` prints a shell with the configuration in `data-config`.
- `js/night-window.js` calculates the times with adhan in the browser, draws the interface, and stores the chosen city and start date in `localStorage` (`lq-night-window`).
- Geolocation uses the browser's time zone and the calculation method of the nearest preset within 1,500 km, otherwise the Muslim World League method. Coordinates never leave the device.

## Calculation methods

| Region | Cities | Method |
|---|---|---|
| Saudi Arabia, Oman | Makkah, Madinah, Riyadh, Muscat | Umm al-Qura |
| UAE, Qatar, Kuwait | Dubai, Doha, Kuwait City | National methods in adhan |
| Egypt, Sudan | Cairo, Khartoum | Egyptian General Authority of Survey |
| Türkiye | Istanbul | Diyanet |
| Iran | Tehran | Tehran |
| South Asia | Kabul, Karachi, Lahore, Delhi, Dhaka | Karachi |
| Southeast Asia | Kuala Lumpur, Singapore, Bandar Seri Begawan, Jakarta, Makassar, Marawi, Narathiwat | 20° / 18° |
| Myanmar | Maungdaw | Karachi |
| Morocco | Casablanca | 19° / 17° |
| Others | Levant, Iraq, North and West Africa, Central Asia, London | Muslim World League |
| United States | New York | ISNA |

Marawi, Narathiwat and Maungdaw use the nearest standard method: no national calculation standard was confirmed for the Philippines, Thailand or Myanmar. Their local timetables (the Bangsamoro religious authority, the Office of the Chularajmontri, and mosques in northern Rakhine) take priority in the launch check.

Before launch, compare each preset against its national authority's published timetable for one date in the last ten nights and adjust the method where they differ by more than two minutes. The interface tells visitors to follow their local mosque where times differ.

## Layout by screen width

| Width | Devices | Night cards | Controls |
|---|---|---|---|
| Under 30rem (480px) | Phones | One row, scrolls sideways, opens on the selected night | Stacked |
| 30rem to 40rem | Small tablets, large phones in landscape | Two rows of five | Stacked |
| 40rem to 56rem | iPad mini, iPad, iPad Air in portrait | Two rows of five | City and start date side by side |
| Over 56rem (896px) | iPad Pro portrait, tablets in landscape, desktops | One row of ten | City and start date side by side |

Tested at 390, 600, 744, 820, 1024, 1180 and 1280 pixels wide, with no horizontal page scrolling at any width.

## Years

The window opens on the Ramadan in progress or the next one, and the arrows move from the previous Ramadan to ten years ahead. Dates come from the Umm al-Qura table in `inc/ramadan.php`; the Ramadan Dates tab can override one year. No yearly update is needed.

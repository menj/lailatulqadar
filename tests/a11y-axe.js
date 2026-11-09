// Accessibility check with axe-core and Playwright (WCAG 2.2 AA).
// Run against a local WordPress that has the theme active:
//   A11Y_COOKIE=name=value (optional) sends a cookie, for a test site that picks its theme that way.
//   A11Y_BASE=http://127.0.0.1:8819 NODE_PATH=<dir with playwright and axe-core> node tests/a11y-axe.js [path ...]
// Exit code 1 on any violation, moderate and minor included (landmark-unique, empty-table-header). A11Y_IGNORE=rule,rule skips known rules.
const { chromium } = require( 'playwright' );
const axeSource = require( 'fs' ).readFileSync( require.resolve( 'axe-core/axe.min.js' ), 'utf8' );
const base = process.env.A11Y_BASE || 'http://127.0.0.1:8819';
// The guide pages in English and Malay, then the search and not-found views in both languages.
const defaults = [ '/', '/ms/', '/about/', '/faq/', '/hadith-and-sources/', '/islamic-dilemma/', '/last-ten-nights-of-ramadan/', '/laylat-al-qadr-dua/', '/laylat-al-qadr-prayer/', '/ms/amalan-malam-lailatulqadar/', '/ms/apa-itu-lailatulqadar/', '/ms/bilakah-lailatulqadar/', '/ms/dilema-islam/', '/ms/doa-lailatulqadar/', '/ms/hadis-dan-sumber-rujukan/', '/ms/mula-di-sini/', '/ms/perancang-malam/', '/ms/peta-laman/', '/ms/sepuluh-malam-terakhir-ramadan/', '/ms/soalan-lazim/', '/ms/tanda-tanda-lailatulqadar/', '/ms/terjemahan-surah-al-qadr/', '/night-planner/', '/signs-of-laylat-al-qadr/', '/sitemap/', '/start-here/', '/surah-al-qadr/', '/what-is-laylat-al-qadr/', '/when-is-laylat-al-qadr/', '/?s=laylat', '/ms/?s=lailatulqadar', '/nope-404-page/', '/ms/nope-404-page/' ];
const paths = process.argv.length > 2 ? process.argv.slice( 2 ) : defaults;
( async () => {
	const browser = await chromium.launch();
	let failed = 0;
	for ( const width of [ 1280, 375 ] ) {
		const context = await browser.newContext( { viewport: { width, height: 800 }, reducedMotion: 'reduce' } );
		if ( process.env.A11Y_COOKIE ) {
			const [ name, value ] = process.env.A11Y_COOKIE.split( '=' );
			await context.addCookies( [ { name, value, url: base } ] );
		}
		for ( const path of paths ) {
			const page = await context.newPage();
			await page.goto( base + path, { waitUntil: 'load' } );
			await page.evaluate( axeSource );
			const result = await page.evaluate( () => axe.run( document, { runOnly: { type: 'tag', values: [ 'wcag2a', 'wcag2aa', 'wcag21a', 'wcag21aa', 'wcag22aa', 'best-practice' ] } } ) );
			const ignore = ( process.env.A11Y_IGNORE || '' ).split( ',' );
			const bad = result.violations.filter( ( v ) => ! ignore.includes( v.id ) );
			failed += bad.length;
			console.log( ( bad.length ? 'FAIL ' : 'ok   ' ) + width + ' ' + path + ( bad.length ? ' ' + bad.map( ( v ) => v.id + ' x' + v.nodes.length ).join( ', ' ) : '' ) );
			await page.close();
		}
		await context.close();
	}
	await browser.close();
	process.exit( failed ? 1 : 0 );
} )();

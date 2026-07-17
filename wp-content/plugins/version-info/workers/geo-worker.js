/**
 * Version Info Geolocation Worker
 *
 * Reads Cloudflare's edge geoIP data from request.cf and returns a JSON
 * payload describing the location of the requesting IP. This is what the
 * Version Info PRO plugin calls when "Server Location Auto-Detect" is set
 * to "Version Info Geolocation (anonymous)".
 *
 * Deployed to: geo.versioninfoplugin.com
 *
 * Privacy: this Worker logs nothing — it only echoes back the CF edge
 * geoIP data that Cloudflare already attached to every request before
 * the Worker was invoked. No analytics, no third-party calls, no DB.
 */

const ALLOWED_ORIGINS = '*'; // Plugin calls server-side via wp_remote_get, but allow browser fallback too.

export default {
  async fetch(request) {
    const url = new URL(request.url);

    // Health check / root.
    if (url.pathname === '/' || url.pathname === '/health') {
      return jsonResponse({
        service: 'version-info-geo',
        status: 'ok',
        docs: 'https://docs.versioninfoplugin.com/pro-features-server-location/',
      });
    }

    if (url.pathname !== '/locate') {
      return jsonResponse({ error: 'Not found' }, 404);
    }

    const cf = request.cf || {};
    const ip = request.headers.get('cf-connecting-ip') || '';

    const payload = {
      status: 'success',
      ip,
      city: cf.city || '',
      region: cf.region || '',
      regionCode: cf.regionCode || '',
      country: cf.country || '',
      countryCode: cf.country || '',
      postalCode: cf.postalCode || '',
      timezone: cf.timezone || '',
      latitude: cf.latitude || '',
      longitude: cf.longitude || '',
      // Hosting / network metadata (CF resolves the IP's ASN).
      provider: cf.asOrganization || '',
      asn: cf.asn || 0,
      // Which CF datacenter served this request — useful for support tickets.
      colo: cf.colo || '',
    };

    return jsonResponse(payload);
  },
};

function jsonResponse(body, status = 200) {
  return new Response(JSON.stringify(body), {
    status,
    headers: {
      'content-type': 'application/json; charset=utf-8',
      'access-control-allow-origin': ALLOWED_ORIGINS,
      'cache-control': 'public, max-age=86400', // CF caches up to a day; the plugin caches 30 days.
      'x-vi-worker': 'geo-1.0',
    },
  });
}

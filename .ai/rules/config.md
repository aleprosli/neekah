---
paths:
  - config/filesystems.php
---

# Config

## Uploaded media stays on the disk named "public", local or R2
MEDIA_DISK picks the driver behind the disk named "public": "local" is storage/app/public, "r2" is the Cloudflare bucket (R2_ACCESS_KEY_ID, R2_SECRET_ACCESS_KEY, R2_BUCKET, R2_ENDPOINT, R2_URL). The name never changes, so the twenty-odd Storage::disk('public') call sites and the 36 Storage::fake('public') calls in the suite need no edit. Do not introduce a second disk name for media.

R2 needs use_path_style_endpoint = true (it answers on endpoint/bucket, never bucket.endpoint) and region 'auto'. R2_URL is the bucket's custom domain, which is what puts images behind Cloudflare's cache; without it Storage::url() has nothing to build from.

The s3 driver needs league/flysystem-aws-s3-v3, which is NOT installed yet.

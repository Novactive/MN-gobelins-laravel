import { UrlGenerator } from "laravel-image";

/**
 * Wrapper utility to contain global configuration options.
 */

const urlGenerator = new UrlGenerator({
  filters_format: "-image({filter})"
});

const MEDIA_DIR = "/media/xl";

export default function imageUrl(path, width, height, options) {
  // Path can contain spaces, that we need to escape.
  const url = `${MEDIA_DIR}/${encodeURIComponent(path)}`;

  if (!process.env.NODE_ENV || process.env.NODE_ENV === 'development') {
    return `${process.env.MIX_PROD_URL}/${url}`;
  } else {
    return urlGenerator.make(url, width, height, options);
  }
}

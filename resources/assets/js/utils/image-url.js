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
  return urlGenerator.make(url, width, height, options);
  // const url = `${MEDIA_DIR}/${encodeURI(path)}`;
  // if (!process.env.NODE_ENV || process.env.NODE_ENV === 'development') {
  //   return `https://collection.mobiliernational.culture.gouv.fr/${url}`;
  // } else {
  //   return urlGenerator.make(url, width, height, options);
  // }
}

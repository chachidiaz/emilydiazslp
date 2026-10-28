export default function (eleventyConfig) {
  // Copy static files (CSS, images) straight into the built site
  eleventyConfig.addPassthroughCopy("src/assets");

  return {
    dir: {
      input: "src",
      includes: "_includes",
      data: "_data",
      output: "_site",
    },
  };
}

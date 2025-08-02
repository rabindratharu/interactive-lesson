// postcss.config.js
module.exports = {
  plugins: [
    require('autoprefixer')({
      overrideBrowserslist: ['last 2 versions', '> 1%', 'ie >= 11']
    })
  ]
}
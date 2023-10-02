const withLinaria = require('next-with-linaria')

/** @type {import('next-with-linaria').LinariaConfig} */
const nextConfig = {
  experimental: {
    serverActions: true,
  },
}

module.exports = withLinaria(nextConfig)

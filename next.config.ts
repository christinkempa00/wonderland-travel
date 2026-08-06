import type { NextConfig } from "next";

const nextConfig: NextConfig = {
  // Situs ini murni presentasional (tidak ada API routes/middleware/SSR dinamis),
  // jadi di-export sebagai file statis murni untuk diupload ke public_html Hostinger.
  output: "export",
};

export default nextConfig;

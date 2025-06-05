import {Helmet} from 'react-helmet-async';

const MetaTags = ({ title, description, image, url }) => (
  <Helmet>
    <title>{title || "Nhà Xe Mỹ Duyên"}</title>
    <meta name="description" content={description || "Dịch vụ xe khách chất lượng cao"} />
    <meta property="og:title" content={title || "Nhà Xe Mỹ Duyên"} />
    <meta property="og:description" content={description || "Dịch vụ xe khách chất lượng cao"} />
    <meta property="og:image" content={image || "/logo.png"} />
    <meta property="og:url" content={url || (typeof window !== 'undefined' ? window.location.href : '')} />
    <meta name="twitter:card" content="summary_large_image" />
    <link rel="icon" href="/favicon.ico" />
  </Helmet>
);

export default MetaTags;
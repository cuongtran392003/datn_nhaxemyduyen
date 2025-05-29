import { renderToStaticMarkup } from 'react-dom/server';
import formatHTML from './formatHTML.js';

export default function ShowRenderedHTML({children}) {
  const markup = renderToStaticMarkup(
    <html>
      <head />
      <body>{children}</body>
    </html>
  );
  return (
    <>
      <h1>Rendered HTML:</h1>
      <pre>
        {formatHTML(markup)}
      </pre>
    </>
  );
}
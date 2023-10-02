import { getRedirects } from './actions';

export default function Home() {
  return (
    <main>
      <h1>
        <span>Detect</span> Redirect
      </h1>
      <p>Unshorten and trace URLs</p>
      <form action={getRedirects}>
        <input placeholder="somesite.com/posts" name="link" />
        <button type="submit">Trace</button>
      </form>
    </main>
  );
}

import { getRedirects } from './actions';

export default function Home() {
  return (
    <main>
      Hello
      <form action={getRedirects}>
        <input placeholder="Website address" name="link" />
        <button>Trace</button>
      </form>
    </main>
  );
}

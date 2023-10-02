'use server';

export async function getRedirects(formData: FormData) {
  const link = formData.get('link');
  if (!link || typeof link !== 'string') return 'Could not process';

  const recursiveResult = await recursiveFetchWithManualRedirect(link);
  console.log('recursiveResult', recursiveResult);

  const response = await fetch(link, {
    method: 'HEAD',
    // redirect: 'manual',
    redirect: 'manual',
  });
  const currentLink = response.url;
  const currentStatus = response.status;
  const currentStatusText = response.statusText;
  const nextLink = response.headers.get('location');

  const stateSymbol = Object.getOwnPropertySymbols(response).find(
    (symbol) => symbol.description === 'state',
  );
  // @ts-expect-error
  const state = response[stateSymbol];
  if (!state) return 'No response state found';

  console.log('state', state);
  const { timingInfo } = state;
  const duration = timingInfo.endTime - timingInfo.startTime;
  console.log('request duration', duration);
}

type RecursiveFetchState = {
  link: string;
  status: number;
  statusText: string;
  nextLink: string | null;
  duration: number | null;
};

async function recursiveFetchWithManualRedirect(
  link: string,
  collectedData: RecursiveFetchState[] = [],
) {
  const response = await fetch(link, {
    method: 'HEAD',
    // redirect: 'manual',
    redirect: 'manual',
    headers: {
      // "Accept-language: en\r\n" .
      // "Cookie: fqtr=iam\r\n" .
      // ($_GET['context'] === 'my') ? "User-agent: ".$_SERVER['HTTP_USER_AGENT'] : "User-agent: Safari Google fqtr\r\n"

      'Accept-language': 'en',
      Cookie: 'ginzart=iam',
      'User-Agent': 'Safari Google ginzart',
    },
  });
  const currentLink = response.url;
  const currentStatus = response.status;
  const currentStatusText = response.statusText;
  const nextLink = response.headers.get('location');

  const stateSymbol = Object.getOwnPropertySymbols(response).find(
    (symbol) => symbol.description === 'state',
  );
  // @ts-expect-error
  const state = response[stateSymbol];
  if (!state) return 'No response state found';

  console.log('state', state);
  const { timingInfo } = state;
  const duration = timingInfo === null ? null : timingInfo.endTime - timingInfo.startTime;

  const newState = {
    link: response.url,
    status: response.status,
    statusText: response.statusText,
    nextLink,
    duration,
  };
  const preservedData = [...collectedData, newState];

  if (nextLink) {
    return recursiveFetchWithManualRedirect(nextLink, preservedData);
  } else {
    return preservedData;
  }
}

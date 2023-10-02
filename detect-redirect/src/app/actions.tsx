'use server';

export async function getRedirects(formData: FormData) {
  const link = formData.get('link');
  if (!link || typeof link !== 'string') return 'Could not process';

  // const requestHeaders = headers();
  // const exposedUserHeaders = {
  //   'User-agent': requestHeaders.get('User-agent') ?? undefined,
  //   Cookie: requestHeaders.get('Cookie') ?? undefined,
  // }

  const recursiveResult = await recursiveFetchWithManualRedirect(link, undefined);
  console.log('recursiveResult', recursiveResult);

  const totalDuration = recursiveResult.reduce((prev, cur) => prev + cur.duration, 0);
  console.log('totalDuration', totalDuration);

  const directLinkFasterByTimes = totalDuration / (recursiveResult.at(-1)?.duration ?? 1);
  console.log('directLinkFasterByTimes', directLinkFasterByTimes);
}

const fetchWithProtocolRetry: typeof fetch = async (input, init) => {
  try {
    return await fetch(input, init);
  } catch (error) {
    const foundProtocols = String(input).search(/http(s):\/\//);
    const hasProtocol = foundProtocols !== -1;
    if (hasProtocol) throw error;
    try {
      return await fetch(`https://${input}`, init);
    } catch (error) {
      return await fetch(`http://${input}`, init);
    }
  }
};

type RecursiveFetchState = {
  link: string;
  status: number;
  statusText: string;
  nextLink: string | null;
  duration: number;
  errorCode?: string;
};

async function recursiveFetchWithManualRedirect(
  link: string,
  collectedData: RecursiveFetchState[] = [],
  headers?: Record<string, string | undefined>,
): Promise<RecursiveFetchState[]> {
  const startTime = performance.now();
  const usernameForFakeHeaders = 'ginzart';
  const fetchHeaders = {
    'Accept-language': 'en',
    Cookie: `${usernameForFakeHeaders}=iam`,
    'User-agent': `Safari Google ${usernameForFakeHeaders}`,
    ...headers,
  };

  try {
    const response = await fetchWithProtocolRetry(link, {
      method: 'GET',
      redirect: 'manual',
      mode: 'no-cors',
      cache: 'no-cache',
      headers: fetchHeaders,
    });
    const endTime = performance.now();
    const duration = endTime - startTime;

    const nextLink = response.headers.get('location');

    const newState: RecursiveFetchState = {
      link: response.url,
      status: response.status,
      statusText: response.statusText,
      nextLink,
      duration,
    };
    const preservedData: RecursiveFetchState[] = [...collectedData, newState];

    if (nextLink) {
      return recursiveFetchWithManualRedirect(nextLink, preservedData);
    } else {
      return preservedData;
    }
  } catch (error) {
    const errorState: RecursiveFetchState = {
      link,
      status: 0,
      statusText: '',
      nextLink: null,
      duration: 0,
      errorCode:
        error instanceof Error &&
        error.cause &&
        typeof error.cause === 'object' &&
        'code' in error.cause &&
        typeof error.cause.code === 'string'
          ? error.cause.code
          : 'Unknown error',
    };

    return [...collectedData, errorState];
  }
}

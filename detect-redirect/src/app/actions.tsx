'use server';

import { headers } from 'next/headers';

export async function getRedirects(formData: FormData) {
  const link = formData.get('link');
  if (!link || typeof link !== 'string') return 'Could not process';

  // const requestHeaders = headers();
  // console.log(requestHeaders.get('User-agent'));
  // console.log(requestHeaders.get('Cookie'));

  // const exposedUserHeaders = {
  //   'User-agent': requestHeaders.get('User-agent') ?? undefined,
  //   Cookie: requestHeaders.get('Cookie') ?? undefined,
  // }

  const recursiveResult = await recursiveFetchWithManualRedirect(link, undefined);
  console.log('recursiveResult', recursiveResult);

  const totalDuration = recursiveResult.reduce((prev, cur) => prev + cur.duration, 0);
  console.log('totalDuration', totalDuration);

  const directLinkFasterByTimes = totalDuration / (recursiveResult.at(-1)?.duration ?? 0);
  console.log('directLinkFasterByTimes', directLinkFasterByTimes);
}

const fetchWithAddProtocolAndRetry: typeof fetch = async (input, init) => {
  // TODO if no protocol — try https://, then try http://, then fail. Use nested trycatch if necessary.
  try {
    return await fetch(input, init);
  } catch (error) {
    const foundProtocols = String(input).search(/http(s):\/\//);
    const hasProtocol = foundProtocols !== -1;
    if (hasProtocol) throw error;
    const protocolToSet = String(input).startsWith('https://') ? 'http://' : 'https://';
    // console.log('protocolToSet', protocolToSet);
    return await fetch(`${protocolToSet}${input}`);
  }
};

type RecursiveFetchState = {
  link: string;
  status: number;
  statusText: string;
  nextLink: string | null;
  duration: number;
};

async function recursiveFetchWithManualRedirect(
  link: string,
  collectedData: RecursiveFetchState[] = [],
  headers?: Record<string, string | undefined>,
) {
  const startTime = performance.now();
  const fetchHeaders = {
    // "Accept-language: en\r\n" .
    // "Cookie: fqtr=iam\r\n" .
    // ($_GET['context'] === 'my') ? "User-agent: ".$_SERVER['HTTP_USER_AGENT'] : "User-agent: Safari Google fqtr\r\n"

    'Accept-language': 'en',
    Cookie: 'ginzart=iam',
    'User-agent': 'Safari Google ginzart',
    ...headers,
  };
  // console.log('fetchHeaders', fetchHeaders);
  const response = await fetchWithAddProtocolAndRetry(link, {
    method: 'GET',
    redirect: 'manual',
    mode: 'no-cors',
    cache: 'no-cache',
    headers: fetchHeaders,
  });
  const endTime = performance.now();
  const duration = endTime - startTime;

  // const currentLink = response.url;
  // const currentStatus = response.status;
  // const currentStatusText = response.statusText;
  const nextLink = response.headers.get('location');

  // const stateSymbol = Object.getOwnPropertySymbols(response).find(
  //   (symbol) => symbol.description === 'state',
  // );
  // // @ts-expect-error
  // const state = response[stateSymbol];
  // if (!state) return 'No response state found';

  // console.log('state', state);
  // const { timingInfo } = state;
  // const duration = timingInfo === null ? null : timingInfo.endTime - timingInfo.startTime;

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

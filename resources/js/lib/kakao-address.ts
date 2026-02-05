// resources/js/lib/kakao-address.ts
type AddressResult = {
    address: string;
    latitude: string;  // y
    longitude: string; // x
};

function loadScriptOnce(src: string): Promise<void> {
    return new Promise((resolve, reject) => {
        const exists = document.querySelector(`script[src="${src}"]`);
        if (exists) return resolve();

        const s = document.createElement('script');
        s.src = src;
        s.async = true;
        s.onload = () => resolve();
        s.onerror = () => reject(new Error(`Failed to load script: ${src}`));
        document.head.appendChild(s);
    });
}

async function ensureDaumPostcode(): Promise<void> {
    // 다음 우편번호는 appkey 필요 없음
    await loadScriptOnce('//t1.daumcdn.net/mapjsapi/bundle/postcode/prod/postcode.v2.js');
}

async function ensureKakaoMaps(): Promise<void> {
    const appkey = import.meta.env.VITE_KAKAO_JS_KEY as string | undefined;
    if (!appkey) throw new Error('VITE_KAKAO_JS_KEY 가 설정되어 있지 않습니다.');

    // services(geocoder) 필요 + autoload=false로 안전 로드
    await loadScriptOnce(`//dapi.kakao.com/v2/maps/sdk.js?appkey=${appkey}&libraries=services&autoload=false`);

    // kakao.maps.load 준비
    await new Promise<void>((resolve) => {
        // window.kakao는 스크립트 로드 후 생김
        // eslint-disable-next-line @typescript-eslint/no-explicit-any
        const kakao = (window as any).kakao;
        kakao.maps.load(() => resolve());
    });
}

async function geocode(address: string): Promise<{ latitude: string; longitude: string }> {
    // eslint-disable-next-line @typescript-eslint/no-explicit-any
    const kakao = (window as any).kakao;
    const geocoder = new kakao.maps.services.Geocoder();

    return new Promise((resolve, reject) => {
        geocoder.addressSearch(address, (result: any, status: any) => {
            if (status !== kakao.maps.services.Status.OK || !result?.[0]) {
                return reject(new Error('주소 좌표 변환에 실패했습니다.'));
            }
            resolve({
                longitude: String(result[0].x), // 경도
                latitude: String(result[0].y),  // 위도
            });
        });
    });
}

/**
 * 주소 검색(다음) → 선택된 주소로 좌표 변환(카카오 geocoder)까지 한 번에
 */
export async function openKakaoAddressSearch(): Promise<AddressResult> {
    await ensureDaumPostcode();
    await ensureKakaoMaps();

    return new Promise((resolve, reject) => {
        // eslint-disable-next-line @typescript-eslint/no-explicit-any
        const daum = (window as any).daum;

        new daum.Postcode({
            oncomplete: async (res: any) => {
                try {
                    const address = res.address as string;
                    const { latitude, longitude } = await geocode(address);
                    resolve({ address, latitude, longitude });
                } catch (e) {
                    reject(e);
                }
            },
            onclose: (state: string) => {
                // 사용자가 닫은 경우
            },
        }).open();
    });
}

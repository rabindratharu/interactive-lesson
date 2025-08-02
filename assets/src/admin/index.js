/* *********===================== Setup store ======================********* */
import { AtrcApis, AtrcStore, AtrcRegisterStore } from 'atrc/build/data';

AtrcApis.baseUrl({
    //don't change atrc-global-api-base-url
    key: 'atrc-global-api-base-url',
    // eslint-disable-next-line no-undef
    url: InteractiveLessonLocalize.rest_url,
});

/* Settings */
AtrcApis.register({
    key: 'settings',
    path: 'interactive-lesson/v1/settings',
    type: 'settings',
});

/* Settings Local for user preferance work with Window: localStorage property */
AtrcStore.register({
    key: 'InteractiveLessonLocal',
    type: 'localStorage',
});

// eslint-disable-next-line no-undef
AtrcApis.xWpNonce(InteractiveLessonLocalize.nonce);
AtrcRegisterStore(InteractiveLessonLocalize.store);

import './routes';
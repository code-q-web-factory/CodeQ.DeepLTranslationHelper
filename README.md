# CodeQ.DeepLTranslationHelper

Provides an EEL helper to translate texts via the DeepL API.

## Installation

To use the DeepL API, you need to set your API key in the confiruation of your Neos project:

```yaml
CodeQ:
  DeepLTranslationHelper:
    DeepLService:
      apiAuthKey: 'myapikey'
```

If you are using the free API, you need to change the baseUri:

```yaml
CodeQ:
  DeepLTranslationHelper:
    DeepLService:
      baseUri: 'https://api-free.deepl.com/v2/'
      apiAuthKey: 'myapikey'
```

## Usage

To translate texts, use the following Fusion code. The first parameter is the text to be translated and the second parameter is the target language code.

```neosfusion
translatedText = ${DeepL.translate('<p>Hello, world!</p>', 'de')}
```

You can also set the source language code as third parameter. If you don't do it, DeepL will guess the source language.

```neosfusion
translatedText = ${DeepL.translate('<p>Hello, world!</p>', 'de', 'en')}
```

The helper is preconfigured to preserve HTML entities while translating.

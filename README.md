# CodeQ.DeepLTranslationHelper

## Deprecated in favor of [Sitegeist.LostInTranslation](https://github.com/sitegeist/Sitegeist.LostInTranslation)

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
      useFreeApi: true
      apiAuthKey: 'myapikey'
```

## Usage

To translate texts, use the following Fusion code. The first parameter is the text to be translated and the second parameter is the target language code.

```neosfusion
translatedText = ${DeepL.translate('<p>Hello, world!</p>', 'de')}
```

You can also set the source language code as third parameter. If you don't do it, DeepL will guess the source language. If the source and the target language code are the same, the helper will return the text without sending it to the DeepL API.

```neosfusion
translatedText = ${DeepL.translate('<p>Hello, world!</p>', 'de', 'en')}
```

The helper is also preconfigured to preserve HTML entities while translating.

## Caching

The requests are cached with a default lifetime of one month. The hashed text and the target language code are being used as cache identifier. To change the cache backend or the lifetime, copy this configuration in your project's `Caches.yaml` file and adjust it as you like.

```yaml
CodeQ_DeepLTranslationHelper_Translation:
  frontend: Neos\Cache\Frontend\VariableFrontend
  backend: Neos\Cache\Backend\FileBackend
  backendOptions:
    defaultLifetime: 2592000
```


## Node Translations 

When nodes are copied (adopted) into another languge the fields can be translated automatically.

The following setting enables the translation of all inlineEditable properties. 

```yaml
CodeQ:
  DeepLTranslationHelper:
    nodeTranslations:
      translateInlineEditables: true
```

Other properties of type string can be translated aswell with the following configuration.

 ```yaml
Neos.Neos:Document:
   properties:
     title:
       options:
         deeplTranslate: true
```

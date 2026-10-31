<?php echo '<?xml version="1.0" encoding="UTF-8"?>'; ?>
<rss version="2.0">
  <channel>
    <title>{{ $siteName }}</title>
    <link>{{ $base }}/</link>
    <description>{{ $description }}</description>
    <language>pt-BR</language>
    <lastBuildDate>{{ now()->toRssString() }}</lastBuildDate>
@foreach ($articles as $article)
    <item>
      <title>{{ $article->title }}</title>
      <link>{{ $base }}/noticia/{{ $article->slug }}</link>
      <guid isPermaLink="true">{{ $base }}/noticia/{{ $article->slug }}</guid>
      <pubDate>{{ $article->published_at?->toRssString() }}</pubDate>
      <description><![CDATA[{{ $article->summary }}]]></description>
@if ($article->author)
      <author>{{ $article->author->public_name ?? $article->author->name }}</author>
@endif
    </item>
@endforeach
  </channel>
</rss>

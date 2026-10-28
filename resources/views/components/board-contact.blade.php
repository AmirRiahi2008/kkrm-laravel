@if($href=P::href($item['contact_url']??null))
<a class="board-contact-link" href="{{ $href }}" target="_blank" rel="noopener noreferrer"><x-icon name="message-circle" size="16" /><span>{{ $item['contact_label']??P::t('راه ارتباطی') }}</span><x-icon name="arrow-up-right" size="14" /></a>
@endif

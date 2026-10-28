@props(['name', 'size' => 24])
<svg {{ $attributes->class(['lucide', 'lucide-'.$name])->merge(['xmlns'=>'http://www.w3.org/2000/svg','width'=>$size,'height'=>$size,'viewBox'=>'0 0 24 24','fill'=>'none','stroke'=>'currentColor','stroke-width'=>2,'stroke-linecap'=>'round','stroke-linejoin'=>'round','aria-hidden'=>'true']) }}>
@foreach(P::data('icons')[$name]??[] as [$tag,$attrs])
<{{ $tag }} @foreach($attrs as $key=>$value) {{ $key }}="{{ $value }}" @endforeach />
@endforeach
</svg>

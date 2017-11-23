<li>
    <span class="toggle-expand pull-left">
    @if(isset($item['children']))
        <i class="fa fa-caret-right"></i>
    @endif
    </span>

    <span>{{ $item['num'] }}</span>

    <span>
        <a
            {!! (isset($item['children']) ? 'tabindex="-1"' : '') !!}
            href="#"
            class="menu-btn {{ empty($item['required'])? '' :'in-active'}}"
            {!! (empty($item['required'])? '' :'data-toggle="tooltip" data-placement="right" title="disabled"') !!}
            req="{{ empty($item['required'])? 'null' :'in-active' }}"
            data-parent-id="{{ $item['parent_id'] }}"
            data-item-id="{{ $item['id'] }}"
            data-prev-id="{{ $item['prev'] }}"
            data-next-id="{{ $item['next'] }}"
        >
            {!! (empty($item['required'])? $item['text'] : '<strike>'.$item['text'].'</strike>') !!}
        </a>
    </span>

    @if(isset($item['children']))
    <ul>
    @each('eon.storyline2::partials.items', $item['children'], 'item')
    </ul>
    @endif
</li>
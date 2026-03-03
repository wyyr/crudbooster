@foreach ($addaction as $a)
    <?php
    foreach ($row as $key => $val) {
        $a['url'] = str_replace('[' . $key . ']', $val ?? '', $a['url']);
    }
    
    $confirm_box = '';
    $onclick_attr = '';
    if (isset($a['confirmation']) && !empty($a['confirmation'])) {
        $c_title = !empty($a['confirmation_title']) ? $a['confirmation_title'] : cbLang('confirmation_title');
        $c_text = !empty($a['confirmation_text']) ? $a['confirmation_text'] : cbLang('confirmation_text');
        $c_type = !empty($a['confirmation_type']) ? $a['confirmation_type'] : 'warning';
        $c_showCancel = isset($a['confirmation_showCancelButton']) && $a['confirmation_showCancelButton'] == 'false' ? 'false' : 'true';
        $c_color = !empty($a['confirmation_confirmButtonColor']) ? $a['confirmation_confirmButtonColor'] : '#DD6B55';
        $c_yesText = !empty($a['confirmation_confirmButtonText']) ? $a['confirmation_confirmButtonText'] : cbLang('confirmation_yes');
        $c_noText = !empty($a['confirmation_cancelButtonText']) ? $a['confirmation_cancelButtonText'] : cbLang('confirmation_no');
        $c_closeOnConfirm = isset($a['confirmation_closeOnConfirm']) && $a['confirmation_closeOnConfirm'] == 'false' ? 'false' : 'true';

        $confirm_box = 'Swal.fire({
            title: "'.addslashes($c_title).'",
            text: "'.addslashes($c_text).'",
            icon: "'.$c_type.'",
            showCancelButton: '.$c_showCancel.',
            confirmButtonColor: "'.$c_color.'",
            confirmButtonText: "'.addslashes($c_yesText).'",
            cancelButtonText: "'.addslashes($c_noText).'",
            closeOnConfirm: '.$c_closeOnConfirm.' 
        }).then((result) => {
            if (result.isConfirmed) {
                window.location.href="'.$a['url'].'";
            }
        });';
    
        $onclick_attr = "onclick='$confirm_box return false;'";
    }
    
    $label = $a['label'] ?? '';
    $title = $a['title'] ?? ($a['label'] ?? '');
    $icon = $a['icon'] ?? '';
    $color = $a['color'] ?? 'primary';
    $target = $a['target'] ?? '_self';
    $url = isset($a['confirmation']) && !empty($a['confirmation']) ? 'javascript:;' : $a['url'];
    
    $show_it = true;
    if (isset($a['showIf'])) {
        $query = $a['showIf'];
    
        foreach ($row as $key => $val) {
            $val_esc = is_numeric($val) ? $val : '"' . addslashes($val) . '"';
            $query = str_replace('[' . $key . ']', $val_esc, $query);
        }
    
        try {
            $show_it = eval("return ($query);");
        } catch (\Throwable $e) {
            $show_it = false;
        }
    }
    
    if ($show_it) {
        echo "<a class='btn btn-sm btn-$color' title='" . addslashes($title) . "' $onclick_attr href='$url' target='$target'><i class='$icon'></i> $label</a>&nbsp;";
    }
    ?>
@endforeach

@if ($button_action_style == 'button_text')

    @if (CRUDBooster::isRead() && ($button_detail ?? true))
        <a class="btn btn-sm btn-primary btn-detail" title="{{ cbLang('action_detail_data') }}"
            href='{{ CRUDBooster::mainpath('detail/' . $row->$pk) . '?return_url=' . urlencode(Request::fullUrl()) }}'>{{ cbLang('action_detail_data') }}</a>
    @endif

    @if (CRUDBooster::isUpdate() && ($button_edit ?? true))
        <a class="btn btn-sm btn-success btn-edit" title="{{ cbLang('action_edit_data') }}"
            href='{{ CRUDBooster::mainpath('edit/' . $row->$pk) . '?return_url=' . urlencode(Request::fullUrl()) . '&parent_id=' . g('parent_id') . '&parent_field=' . ($parent_field ?? '') }}'>{{ cbLang('action_edit_data') }}</a>
    @endif

    @if (CRUDBooster::isDelete() && ($button_delete ?? true))
        <?php $url_delete = CRUDBooster::mainpath('delete/' . $row->$pk); ?>
        <a class='btn btn-sm btn-warning btn-delete' title='{{ cbLang('action_delete_data') }}' href='javascript:;'
            onclick='{{ CRUDBooster::deleteConfirm($url_delete) }}'>{{ cbLang('action_delete_data') }}</a>
    @endif
@elseif($button_action_style == 'button_icon_text')
    @if (CRUDBooster::isRead() && ($button_detail ?? true))
        <a class='btn btn-sm btn-primary btn-detail' title='{{ cbLang('action_detail_data') }}'
            href='{{ CRUDBooster::mainpath('detail/' . $row->$pk) . '?return_url=' . urlencode(Request::fullUrl()) }}'><i
                class="fas fa-eye"></i> {{ cbLang('action_detail_data') }}</a>
    @endif

    @if (CRUDBooster::isUpdate() && ($button_edit ?? true))
        <a class='btn btn-sm btn-success btn-edit' title='{{ cbLang('action_edit_data') }}'
            href='{{ CRUDBooster::mainpath('edit/' . $row->$pk) . '?return_url=' . urlencode(Request::fullUrl()) . '&parent_id=' . g('parent_id') . '&parent_field=' . $parent_field }}'><i
                class="fas fa-pencil-alt"></i> {{ cbLang('action_edit_data') }}</a>
    @endif

    @if (CRUDBooster::isDelete() && ($button_delete ?? true))
        <?php $url = CRUDBooster::mainpath('delete/' . $row->$pk); ?>
        <a class='btn btn-sm btn-warning btn-delete' title='{{ cbLang('action_delete_data') }}' href='javascript:;'
            onclick='{{ CRUDBooster::deleteConfirm($url) }}'><i class='fa fa-trash'></i>
            {{ cbLang('action_delete_data') }}</a>
    @endif
@elseif($button_action_style == 'dropdown')
    <div class='btn-group btn-group-action'>
        <button type='button' class='btn btn-sm btn-primary btn-action'>{{ cbLang('action_label') }}</button>
        <button type='button' class='btn btn-sm btn-primary dropdown-toggle' data-toggle='dropdown'>
            <span class='caret'></span>
            <span class='sr-only'>Toggle Dropdown</span>
        </button>
        <ul class='dropdown-menu dropdown-menu-action' role='menu'>
            @foreach ($addaction as $a)
                <?php
                foreach ($row as $key => $val) {
                    $a['url'] = str_replace('[' . $key . ']', $val, $a['url']);
                }
                
                $label = $a['label'];
                $url = $a['url'] . '?return_url=' . urlencode(Request::fullUrl());
                $icon = $a['icon'];
                $color = $a['color'] ?: 'primary';
                
                if (isset($a['showIf'])) {
                    $query = $a['showIf'];
                
                    foreach ($row as $key => $val) {
                        $query = str_replace('[' . $key . ']', '"' . $val . '"', $query);
                    }
                
                    @eval("if($query) {
                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                        echo \"<li><a title='\$label' href='\$url'><i class='\$icon'></i> \$label</a></li>\";
                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                    }");
                } else {
                    echo "<li><a title='$label' href='$url'><i class='$icon'></i> $label</a></li>";
                }
                ?>
            @endforeach

            @if (CRUDBooster::isRead() && $button_detail)
                <li><a class='btn-detail' title='{{ cbLang('action_detail_data') }}'
                        href='{{ CRUDBooster::mainpath('detail/' . $row->$pk) . '?return_url=' . urlencode(Request::fullUrl()) }}'><i
                            class="fas fa-eye"></i> {{ cbLang('action_detail_data') }}</a></li>
            @endif

            @if (CRUDBooster::isUpdate() && $button_edit)
                <li><a class='btn-edit' title='{{ cbLang('action_edit_data') }}'
                        href='{{ CRUDBooster::mainpath('edit/' . $row->$pk) . '?return_url=' . urlencode(Request::fullUrl()) . '&parent_id=' . g('parent_id') . '&parent_field=' . $parent_field }}'><i
                            class="fas fa-pencil-alt"></i> {{ cbLang('action_edit_data') }}</a></li>
            @endif

            @if (CRUDBooster::isDelete() && $button_delete)
                <?php $url = CRUDBooster::mainpath('delete/' . $row->$pk); ?>
                <li><a class='btn-delete' title='{{ cbLang('action_delete_data') }}' href='javascript:;'
                        onclick='{{ CRUDBooster::deleteConfirm($url) }}'><i class='fa fa-trash'></i>
                        {{ cbLang('action_delete_data') }}</a></li>
            @endif
        </ul>
    </div>
@else
    @if (CRUDBooster::isRead() && $button_detail)
        <a class="btn btn-sm btn-primary btn-detail" title="{{ cbLang('action_detail_data') }}"
            href='{{ CRUDBooster::mainpath('detail/' . $row->$pk) . '?return_url=' . urlencode(Request::fullUrl()) }}'>
            <i class="fas fa-eye"></i>
        </a>
    @endif

    @if (CRUDBooster::isUpdate() && $button_edit)
        <a class="btn btn-sm btn-success btn-edit" title="{{ cbLang('action_edit_data') }}"
            href='{{ CRUDBooster::mainpath('edit/' . $row->$pk) . '?return_url=' . urlencode(Request::fullUrl()) . '&parent_id=' . g('parent_id') . '&parent_field=' . $parent_field }}'>
            <i class="fas fa-pencil-alt"></i>
        </a>
    @endif

    @if (CRUDBooster::isDelete() && $button_delete)
        <?php $url = CRUDBooster::mainpath('delete/' . $row->$pk); ?>
        <a class="btn btn-sm btn-warning btn-delete" title="{{ cbLang('action_delete_data') }}" href="javascript:;"
            onclick='{{ CRUDBooster::deleteConfirm($url) }}'>
            <i class="fas fa-trash"></i>
        </a>
    @endif

@endif

@extends('crudbooster::admin_template')

@section('content')

    @if($index_statistic)
        <div id='box-statistic' class='row'>
            @foreach($index_statistic as $stat)
                <div class="{{ ($stat['width'])?:'col-sm-3' }}">
                    <div class="small-box bg-{{ $stat['color']?:'red' }}">
                        <div class="inner">
                            <h3>{{ $stat['count'] }}</h3>
                            <p>{{ $stat['label'] }}</p>
                        </div>
                        <div class="icon">
                            <i class="{{ $stat['icon'] }}"></i>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    @endif

    @if(!is_null($pre_index_html) && !empty($pre_index_html))
        {!! $pre_index_html !!}
    @endif


    @if(g('return_url'))
        <p><a href='{{g("return_url")}}'><i class='fa fa-chevron-circle-{{ cbLang('left') }}'></i>
                &nbsp; {{cbLang('form_back_to_list',['module'=>urldecode(g('label'))])}}</a></p>
    @endif

    @if($parent_table)
        <div class="card">
            <div class="card-body table-responsive no-padding">
                <table class='table table-bordered'>
                    <tbody>
                    <tr class='active'>
                        <td colspan="2"><strong><i class='fa fa-bars'></i> {{ ucwords(urldecode(g('label'))) }}</strong></td>
                    </tr>
                    @foreach(explode(',',urldecode(g('parent_columns'))) as $c)
                        <tr>
                            <td width="25%"><strong>
                                    @if(urldecode(g('parent_columns_alias')))
                                        {{explode(',',urldecode(g('parent_columns_alias')))[$loop->index]}}
                                    @else
                                        {{  ucwords(str_replace('_',' ',$c)) }}
                                    @endif
                                </strong></td>
                            <td> {{ $parent_table->$c }}</td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @endif

    <div class="card">
        <div class="card-header">
            <div class="row justify-content-between w-100">
                <div class="col-md-6">
                    <div class="form-check selected-action">
                        @if($button_bulk_action && ( ($button_delete && CRUDBooster::isDelete()) || $button_selected) )
                            <button type="button" class="btn btn-outline-primary btn-sm dropdown-toggle" data-toggle="dropdown" aria-expanded="false">
                                <i class="fas fa-check-square"></i> {{ cbLang("button_selected_action") }}
                            </button>
                            <ul class="dropdown-menu">
                                @if($button_delete && CRUDBooster::isDelete())
                                    <li>
                                        <a class="dropdown-item" href="javascript:void(0)" data-name="delete" title="{{ cbLang('action_delete_selected') }}">
                                            <i class="fa fa-trash"></i> {{ cbLang('action_delete_selected') }}
                                        </a>
                                    </li>
                                @endif

                                @if($button_selected)
                                    @foreach($button_selected as $button)
                                        <li>
                                            <a href="javascript:void(0)" data-name="{{ $button["name"] }}" title="{{ $button["label"] }}">
                                                <i class="fa fa-{{$button['icon']}}"></i> {{ $button['label'] }}
                                            </a>
                                        </li>
                                    @endforeach
                                @endif

                            </ul><!--end-dropdown-menu-->
                        @endif
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="float-right">
                        @if($button_filter)
                            <a href="javascript:void(0)" id="btn_advanced_filter" data-url-parameter='{{$build_query}}'
                               title="{{cbLang('filter_dialog_title')}}" class="btn btn-sm btn-primary {{ (Request::get('filter_column')) ? 'active' : '' }}">
                                <i class="fa fa-filter"></i> {{ cbLang("button_filter") }}
                            </a>
                        @endif

                        <form method="GET" style="display:inline-block;width: 260px;" action="{{ Request::url() }}">
                            <div class="input-group">
                                <input type="text" name="q" value="{{ Request::get('q') }}" class="form-control input-sm float-{{ cbLang('right') }}"
                                       placeholder="{{cbLang('filter_search')}}"/>
                                {!! CRUDBooster::getUrlParameters(['q']) !!}
                                <div class="input-group-btn">
                                    @if(Request::get('q'))
                                        <?php
                                        $parameters = Request::all();
                                        unset($parameters['q']);
                                        $build_query = urldecode(http_build_query($parameters));
                                        $build_query = ($build_query) ? "?".$build_query : "";
                                        $build_query = (Request::all()) ? $build_query : "";
                                        ?>
                                        <button type='button' onclick='location.href="{{ CRUDBooster::mainpath().$build_query}}"'
                                                title="{{cbLang('button_reset')}}" class='btn btn-sm btn-warning'><i class='fa fa-ban'></i></button>
                                    @endif
                                    <button type="submit" class="btn btn-sm btn-primary"><i class="fa fa-search"></i></button>
                                </div>
                            </div>
                        </form>


                        <form method='get' id='form-limit-paging' style="display:inline-block" action='{{Request::url()}}'>
                            {!! CRUDBooster::getUrlParameters(['limit']) !!}
                            <div class="input-group">
                                <select onchange="$('#form-limit-paging').submit()" name="limit" class="form-control input-sm">
                                    <option {{( $limit==5) ? 'selected' : '' }} value="5">5</option>
                                    <option {{( $limit==10) ? 'selected' : '' }} value="10">10</option>
                                    <option {{( $limit==20) ? 'selected' : '' }} value="20">20</option>
                                    <option {{( $limit==25) ? 'selected' : '' }} value="25">25</option>
                                    <option {{( $limit==50) ? 'selected' : '' }} value="50">50</option>
                                    <option {{( $limit==100) ? 'selected' : '' }} value="100">100</option>
                                    <option {{( $limit==200) ? 'selected' : '' }} value="200">200</option>
                                </select>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
        <div class="card-body table-responsive no-padding">
            @include("crudbooster::default.table")
        </div>
    </div>

    @if(!is_null($post_index_html) && !empty($post_index_html))
        {!! $post_index_html !!}
    @endif

@endsection

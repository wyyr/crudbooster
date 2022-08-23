@extends("crudbooster::admin_template")
@section("content")

<ul class="nav nav-tabs nav-primary mb-1">
    <li class="nav-item" role="presentation">
        <a class="nav-link" href="{{ Route('ModulsControllerGetStep1') . '/' . $id }}">
            <i class="fa fa-info"></i> Step 1 - Module Information
        </a>
    </li>
    <li class="nav-item" role="presentation">
        <a class="nav-link" href="{{ Route('ModulsControllerGetStep2') . '/' . $id }}">
            <i class="fa fa-table"></i> Step 2 - Table Display
        </a>
    </li>
    <li class="nav-item" role="presentation">
        <a class="nav-link" href="{{ Route('ModulsControllerGetStep3') . '/' . $id }}">
            <i class="fa fa-plus-square"></i> Step 3 - Form Display
        </a>
    </li>
    <li class="nav-item" role="presentation">
        <a class="nav-link active" href="{{ Route('ModulsControllerGetStep4') . '/' . $id }}">
            <i class="fa fa-wrench"></i> Step 4 - Configuration
        </a>
    </li>
</ul>

<form method="post" action="{{ Route('ModulsControllerPostStepFinish') }}">
    <div class="card card-light">
        <div class="card-header">
            <h6 class="card-title">Configuration</h6>
        </div>
        {{ csrf_field() }}
        <input type="hidden" name="id" value="{{ $id }}">
        <div class="card-body">

            <div class="row">
                <div class="col-sm-12">
                    <div class="form-group">
                        <label>Title Field Candidate</label>
                        <input type="text" name="title_field" value="{{ $cb_title_field }}" class="form-control">
                    </div>
                </div>

                <div class="col-sm-5">
                    <div class="form-group">
                        <label>Limit Data</label>
                        <input type="number" name="limit" value="{{ $cb_limit }}" class="form-control">
                    </div>
                </div>

                <div class="col-sm-7">
                    <div class="form-group">
                        <label>Order By</label>
                        <?php
                        if (is_array($cb_orderby)) {
                            $orderby = [];
                            foreach ($cb_orderby as $k => $v) {
                                $orderby[] = $k.','.$v;
                            }
                            $orderby = implode(";", $orderby);
                        } else {
                            $orderby = $cb_orderby;
                        }
                        ?>
                        <input type="text" name="orderby" value="{{ $orderby }}" class="form-control">
                        <div class="help-block">E.g : column_name,desc</div>
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-sm-4">
                    <div class="row">

                        <div class="col-sm-12">
                            <div class="form-group">
                                <label>Global Privilege</label>
                                <label class='radio-inline'>
                                    <input type='radio' name='global_privilege' {{($cb_global_privilege)?"checked":""}} value='true'/> TRUE
                                </label>
                                <label class='radio-inline'>
                                    <input {{(!$cb_global_privilege)?"checked":""}} type='radio' name='global_privilege' value='false'/> FALSE
                                </label>
                            </div>
                        </div>

                        <div class="col-sm-12">
                            <div class="form-group">
                                <label>Show Button Table Action</label>
                                <label class='radio-inline'>
                                    <input {{($cb_button_table_action)?"checked":""}} type='radio' name='button_table_action' value='true'/> TRUE
                                </label>
                                <label class='radio-inline'>
                                    <input {{(!$cb_button_table_action)?"checked":""}} type='radio' name='button_table_action' value='false'/> FALSE
                                </label>
                            </div>
                        </div>

                        <div class="col-sm-12">
                            <div class="form-group">
                                <label>Show Bulk Action Button</label>
                                <label class='radio-inline'>
                                    <input {{($cb_button_bulk_action)?"checked":""}} type='radio' name='button_bulk_action' value='true'/> TRUE
                                </label>
                                <label class='radio-inline'>
                                    <input {{(!$cb_button_bulk_action)?"checked":""}} type='radio' name='button_bulk_action' value='false'/> FALSE
                                </label>
                            </div>
                        </div>

                        <div class="col-sm-12">
                            <div class="form-group">
                                <label>Button Action Style</label>
                                <label class='radio-inline'>
                                    <input {{($cb_button_action_style=='button_icon')?"checked":""}} type='radio' name='button_action_style'
                                           value='button_icon'/> Icon
                                </label>
                                <label class='radio-inline'>
                                    <input {{($cb_button_action_style=='button_icon_text')?"checked":""}} type='radio' name='button_action_style'
                                           value='button_icon_text'/> Icon & Text
                                </label>
                                <label class='radio-inline'>
                                    <input {{($cb_button_action_style=='button_text')?"checked":""}} type='radio' name='button_action_style'
                                           value='button_text'/> Button Text
                                </label>
                                <label class='radio-inline'>
                                    <input {{($cb_button_action_style=='button_dropdown')?"checked":""}} type='radio' name='button_action_style'
                                           value='button_dropdown'/> Dropdown
                                </label>
                            </div>
                        </div>


                    </div>
                </div>

                <div class="col-sm-3">
                    <div class="row">

                        <div class="col-sm-12">
                            <div class="form-group">
                                <label>Show Button Add</label>
                                <label class='radio-inline'>
                                    <input {{($cb_button_add)?"checked":""}} type='radio' name='button_add' value='true'/> TRUE
                                </label>
                                <label class='radio-inline'>
                                    <input {{(!$cb_button_add)?"checked":""}} type='radio' name='button_add' value='false'/> FALSE
                                </label>
                            </div>
                        </div>

                        <div class="col-sm-12">
                            <div class="form-group">
                                <label>Show Button Edit</label>
                                <label class='radio-inline'>
                                    <input {{($cb_button_edit)?"checked":""}} type='radio' name='button_edit' value='true'/> TRUE
                                </label>
                                <label class='radio-inline'>
                                    <input {{(!$cb_button_edit)?"checked":""}} type='radio' name='button_edit' value='false'/> FALSE
                                </label>
                            </div>
                        </div>

                        <div class="col-sm-12">
                            <div class="form-group">
                                <label>Show Button Delete</label>
                                <label class='radio-inline'>
                                    <input {{($cb_button_delete)?"checked":""}} type='radio' name='button_delete' value='true'/> TRUE
                                </label>
                                <label class='radio-inline'>
                                    <input {{(!$cb_button_delete)?"checked":""}} type='radio' name='button_delete' value='false'/> FALSE
                                </label>
                            </div>
                        </div>


                        <div class="col-sm-12">
                            <div class="form-group">
                                <label>Show Button Detail</label>
                                <label class='radio-inline'>
                                    <input {{($cb_button_detail)?"checked":""}} type='radio' name='button_detail' value='true'/> TRUE
                                </label>
                                <label class='radio-inline'>
                                    <input {{(!$cb_button_detail)?"checked":""}} type='radio' name='button_detail' value='false'/> FALSE
                                </label>
                            </div>
                        </div>


                    </div>


                </div>

                <div class="col-sm-4">
                    <div class="row">

                        <div class="col-sm-12">
                            <div class="form-group">
                                <label>Show Button Show Data</label>
                                <label class='radio-inline'>
                                    <input {{($cb_button_show)?"checked":""}} type='radio' name='button_show' value='true'/> TRUE
                                </label>
                                <label class='radio-inline'>
                                    <input {{(!$cb_button_show)?"checked":""}} type='radio' name='button_show' value='false'/> FALSE
                                </label>
                            </div>
                        </div>

                        <div class="col-sm-12">
                            <div class="form-group">
                                <label>Show Button Filter & Sorting</label>
                                <label class='radio-inline'>
                                    <input {{($cb_button_filter)?"checked":""}} type='radio' name='button_filter' value='true'/> TRUE
                                </label>
                                <label class='radio-inline'>
                                    <input {{(!$cb_button_filter)?"checked":""}} type='radio' name='button_filter' value='false'/> FALSE
                                </label>
                            </div>
                        </div>

                        <div class="col-sm-12">
                            <div class="form-group">
                                <label>Show Button Import</label>
                                <label class='radio-inline'>
                                    <input {{($cb_button_import)?"checked":""}} type='radio' name='button_import' value='true'/> TRUE
                                </label>
                                <label class='radio-inline'>
                                    <input {{(!$cb_button_import)?"checked":""}} type='radio' name='button_import' value='false'/> FALSE
                                </label>
                            </div>
                        </div>

                        <div class="col-sm-12">
                            <div class="form-group">
                                <label>Show Button Export</label>
                                <label class='radio-inline'>
                                    <input {{($cb_button_export)?"checked":""}} type='radio' name='button_export' value='true'/> TRUE
                                </label>
                                <label class='radio-inline'>
                                    <input {{(!$cb_button_export)?"checked":""}} type='radio' name='button_export' value='false'/> FALSE
                                </label>
                            </div>
                        </div>
                    </div>
                </div>

            </div>

        </div>
        <div class="card-footer">
            <div align="right">
                <button type="button" onclick="location.href='{{ CRUDBooster::mainpath('step3') . '/' . $id }}'"
                        class="btn btn-outline-secondary mr-2">&laquo; {{ cbLang('button_back') }}</button>
                <input type="submit" name="submit" class="btn btn-primary" value="Save Module">
            </div>
        </div>
    </div>
</form>
@endsection
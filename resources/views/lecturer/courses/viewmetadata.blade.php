
<div class="meta-entry-container">

    @foreach($MetadataStore as $key=>$resource)
    <div class="meta-entry shadow" id="{{$resource->id}}"> 
        <div style="overflow-y: auto;">
            <div class="meta-checkbox">
                {{ Form::checkbox('metadata_store_id[]', $resource->id, in_array($resource->id,$MetaId) ? true : false) }}
                {{ Form::hidden('metadata_type_id[]', $resource->metadata_type_id) }}
            </div>
            <div class="meta-description">
                {{ $resource->description }}
            </div>
        </div>
              
        <div class="meta-value">
            {{ Form::text('value[]',empty($resource->course_metadata->value)? '' : $resource->course_metadata->value, array('placeholder'=>'Custom Value','class' => 'form-control')) }}
        </div>

    </div>
    @endforeach

</div>

@if(!$MetadataStore->isEmpty())

<div class="col-lg-8" style="margin-top:50px">
    <button type="submit" class="btn btn-success ">Submit</button>

</div>
@else

@endif

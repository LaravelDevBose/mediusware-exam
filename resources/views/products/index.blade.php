@extends('layouts.app')

@section('content')

    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800">Products</h1>
    </div>


    <div class="card">
        <form action="" method="get" class="card-header">
            <div class="form-row justify-content-between">
                <div class="col-md-2">
                    <input type="text" name="title" placeholder="Product Title" class="form-control" value="{{ isset($_GET['title']) ? $_GET['title'] : null }}">
                </div>
                <div class="col-md-2">
                    <select name="variant" id="" class="form-control" style="min-width: 220px;">
                        <option value="">Select Variant</option>
                        @if(!empty($variants))
                            @foreach($variants as $variant)
                                @if(!empty($variant->product_variants) && count($variant->product_variants) > 0)
                                <optgroup label="{{ $variant->title  }}">
                                    @foreach($variant->product_variants as $product_variant)
                                    <option {{ (!empty($_GET['variant'])&& $_GET['variant'] ==$product_variant->variant) ? 'selected' : '' }}  value="{{ $product_variant->variant }}">{{ $product_variant->variant }}</option>
                                    @endforeach
                                </optgroup>
                                @endif
                            @endforeach
                        @endif
                    </select>
                </div>

                <div class="col-md-3">
                    <div class="input-group">
                        <div class="input-group-prepend">
                            <span class="input-group-text">Price Range</span>
                        </div>
                        <input type="text" name="price_from" value="{{ isset($_GET['price_from']) ? $_GET['price_from'] : null }}" aria-label="First name" placeholder="From" class="form-control">
                        <input type="text" name="price_to" value="{{ isset($_GET['price_to']) ? $_GET['price_to'] : null }}" aria-label="Last name" placeholder="To" class="form-control">
                    </div>
                </div>
                <div class="col-md-2">
                    <input type="date" name="date" value="{{ isset($_GET['date']) ? $_GET['date'] : null }}" placeholder="Date" class="form-control">
                </div>
                <div class="col-md-1">
                    <button type="submit" class="btn btn-primary float-right"><i class="fa fa-search"></i></button>
                </div>
            </div>
        </form>

        <div class="card-body">
            <div class="table-response">
                <table class="table">
                    <thead>
                    <tr>
                        <th>#</th>
                        <th>Title</th>
                        <th>Description</th>
                        <th>Variant</th>
                        <th width="150px">Action</th>
                    </tr>
                    </thead>

                    <tbody>
                    @if(!empty($products) && count($products)> 0)
                        @foreach($products as $product)
                            <tr>
                                <td>{{ $loop->iteration }}</td>
                                <td>{{ $product->title }}<br> Created at : {{ \Carbon\Carbon::parse($product->created_at)->format('d-M-Y') }}</td>
                                <td>{{ $product->description }}</td>
                                <td>
                                    @if(!empty($product->variant_prices))
                                        <dl class="row mb-0 variant" style="height: 80px; overflow: hidden" >
                                            @foreach($product->variant_prices as $variant_price)
                                            <dt class="col-sm-3 pb-0">
                                                @if(!empty($variant_price->variant_one))
                                                    {{ $variant_price->variant_one->variant }}
                                                @endif

                                                @if(!empty($variant_price->variant_two))
                                                    /{{ $variant_price->variant_two->variant }}
                                                @endif

                                                @if(!empty($variant_price->variant_three))
                                                    /{{ $variant_price->variant_three->variant }}
                                                @endif
                                            </dt>
                                            <dd class="col-sm-9">
                                                <dl class="row mb-0">
                                                    <dt class="col-sm-4 pb-0">Price : {{ number_format($variant_price->price,2) }}</dt>
                                                    <dd class="col-sm-8 pb-0">InStock : {{ number_format($variant_price->stock,2) }}</dd>
                                                </dl>
                                            </dd>
                                            @endforeach
                                        </dl>

                                        <button onclick="$(this).parent('td').find('.variant').toggleClass('h-auto')" class="btn btn-sm btn-link">Show more</button>
                                    @endif
                                </td>
                                <td>
                                    <div class="btn-group btn-group-sm">
                                        <a href="{{ route('product.edit', $product->id) }}" class="btn btn-success">Edit</a>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    @endif
                    </tbody>

                </table>
            </div>

        </div>

        <div class="card-footer">
            <div class="row justify-content-between">
                <div class="col-md-6">
                    <p>Showing {{ $products->firstItem() }} to {{ $products->lastItem() }} out of {{ $products->total() }}</p>
                </div>
                <div class="col-md-2">
                    {{ $products->links() }}
                </div>
            </div>
        </div>
    </div>

@endsection

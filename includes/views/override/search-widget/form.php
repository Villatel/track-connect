
<form role="search" method="get" id="searchform" action="<?= home_url( '/' ) ?>"><input type="hidden" value=""
                                                                                        name="s"/><input
        type="hidden" value="listing" name="post_type"/>
    <div id="datepicker-search-container">
        <div class="input-daterange input-group" id="datepicker-search">
            <span>Arrival Date</span>
            <input type="text" class="input-sm form-control" name="checkin" id="checkin_date-search"
                   value="<?= $checkin ?>">
            <span>Departure Date</span>
            <input type="text" class="input-sm form-control" name="checkout" id="checkout_date-search"
                   value="<?= $checkout ?>">
        </div>
    </div>

    <span class="search-locations-header"><strong>Locations</strong></span>

    <?php $current = ! empty( $wp_query->query_vars['tax_query'][0]['terms'] ) ? $wp_query->query_vars['tax_query'][0]['terms'] : ''; ?>
    <select name='locations' id='locations-select' class='wp-listings-taxonomy'>
        <option value="">All</option>
        <?php foreach ( $this->getNodeTypes() as $type ): ?>
            <optgroup label="<?= $type->name ?>">
                <?php foreach ( $this->getNodes( $type ) as $node ): ?>
                    <?php if ( $node->count ): ?>
                        <option
                            value="<?= $node->slug ?>" <?= ( $node->slug == $locations ) ? ' selected ' : '' ?>><?= $node->name ?>
                            (<?= $node->count ?>)
                        </option>
                    <?php endif; ?>
                <?php endforeach; ?>
            </optgroup>
        <?php endforeach; ?>
    </select>
    <br/>

    <span class="sleeps-header"><strong>Sleeps</strong></span>
    <br/>
    <select name="sleeps" class="sleeps-input">
        <option value=""></option>
        <?php for ( $i = 1; $i <= 32; $i ++ ) : ?>
            <option <?= $i == $sleeps ? ' selected ' : ''; ?> value="<?= $i ?>"><?= $i ?></option>
        <?php endfor; ?>
    </select>
    <br/>

    <span class="lodging-header"><b>Lodging Types</b></span>
    <br/>

    <select name="lodging" id="lodging" class="listing-lodging">
        <option value="">No Preference</option>
        <?php foreach ( (array)json_decode(get_option('track_connect_lodging_types')) as $lodgingTypeId => $lodgingTypeValue): ?>
            <option <?= ( $lodgingType == $lodgingTypeId ) ? 'SELECTED' : ''; ?>
                value="<?= $lodgingTypeId ?>"><?= $lodgingTypeValue; ?></option>';
        <?php endforeach; ?>
    </select>
    <br>

    <span class="beds-header"><b>Bedrooms</b></span>
    <input type="hidden" id="lowbed" name="lowbed"/>
    <input type="hidden" id="highbed" name="highbed"/>
    <br/>

    <input class="slider-beds" type="text" id="slider-beds" readonly
           style="border:0; color:#f6931f; font-weight:bold;">
    <br/>

    <div id="bed-range"></div>
    <br/>

    <span class="price-header" for="amount"><strong>Price range</strong></span>
    <input type="hidden" id="lowrate" name="low"/>
    <input type="hidden" id="highrate" name="high"/>
    <br>

    <input class="slider-amount" type="text" id="amount" readonly
           style="border:0; color:#f6931f; font-weight:bold;">
    <br/>

    <div id="price-range"></div>
    <br/>

    <span class="search-amenities-header"><b>Amenities</b></span>
    <div class="listing-amenities" style="max-height:200px;overflow: hidden;overflow-y: scroll">
        <?php foreach ( $listings_taxonomies as $tax => $data ): ?>
            <?php if ( $tax != 'features' || ! isset( $instance[ $tax ] ) || ! $instance[ $tax ] ) {
                continue;
            } ?>

            <?php $terms = get_terms( $tax,
                array( 'orderby' => 'title', 'number' => 100, 'hierarchical' => false ) ); ?>
            <?php if ( empty( $terms ) ) {
                continue;
            } ?>

            <?php $current = ! empty( $wp_query->query_vars['tax_query']['terms'] ) ? $wp_query->query_vars['tax_query']['terms'] : ''; ?>
            <?php foreach ( (array) $terms as $term ): ?>
                <?php if ( in_array( $term->name, $activeAmenities ) ): ?>
                    <input type="checkbox" name="features[]" value="<?= $term->slug ?>"
                           id="<?= $tax ?>" <?= ( in_array( $term->slug, $features ) ) ? ' checked ' : ''; ?>
                           class='wp-listings-taxonomy-checkbox'><?= $term->name ?><br/>
                <?php endif; ?>
            <?php endforeach; ?>
        <?php endforeach; ?>
    </div>
    <br/>
    <div class="btn-search">
        <button type="submit" class="searchsubmit"><i class="fa fa-search"></i><span
                class="button-text"><?= esc_attr( $instance['button_text'] ) ?></span></button>
        <button type="reset" class="searchclear">Reset</button>
    </div>
    <div class="clear"></div>
</form>
<script type="text/javascript">
    jQuery(function ($) {
        $("#bed-range").slider({
            range: true,
            min: <?=$bedsMin->bed ?: 0; ?>,
            max: <?=$bedsMax->bed ?>,
            step: 1,
            values: [ <?=( $lowBed ) ? $lowBed : $bedsMin->bed ?: 0; ?>, <?=( $highBed ) ? $highBed : $bedsMax->bed?> ],
            slide: function (event, ui) {
                $("#slider-beds").val(ui.values[0] + " - " + ui.values[1]);
                $("#lowbed").val(ui.values[0]);
                $("#highbed").val(ui.values[1]);
            }
        });
        $("#slider-beds").val($("#bed-range").slider("values", 0) +
            " - " + $("#bed-range").slider("values", 1));
        $("#lowbed").val($("#bed-range").slider("values", 0));
        $("#highbed").val($("#bed-range").slider("values", 1));

        $("#price-range").slider({
            range: true,
            min: 0,
            max: 2500,
            step: 100,
            values: [0, 2500],
            slide: function (event, ui) {
                var plus = (ui.values[1] == 2500) ? '+' : '';
                $("#amount").val("$" + ui.values[0] + " - $" + ui.values[1] + plus);
                $("#lowrate").val(ui.values[0]);
                $("#highrate").val(ui.values[1]);
            }
        });
        $("#amount").val("$" + $("#price-range").slider("values", 0) +
            " - $" + $("#price-range").slider("values", 1) + '+');
        $("#lowrate").val($("#price-range").slider("values", 0));
        $("#highrate").val($("#price-range").slider("values", 1));

        $('#datepicker-search-container .input-daterange').datepicker({
            autoclose: true,
            clearBtn: true,
            startDate: '0'
        });

        $('#checkin_date-search').datepicker().on('hide', function (e) {
            $('#checkout_date-search').datepicker('show');
        });
    });
</script>
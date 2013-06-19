// This is the color palette
var Palette = [
    [15,15,15],[15,175,15],[95,15,15],[95,175,15],[175,15,15],[175,175,15],[255,15,15],[255,175,15],
    [15,15,95],[15,175,95],[95,15,95],[95,175,95],[175,15,95],[175,175,95],[255,15,95],[255,175,95],
    [15,15,175],[15,175,95],[95,15,175],[95,175,175],[175,15,175],[175,175,175],[255,15,175],[255,175,175],
    [15,15,255],[15,175,255],[95,15,255],[95,175,255],[175,15,255],[175,175,255],[255,15,255],[255,175,255],
    [15,95,15],[15,255,15],[95,95,15],[95,255,15],[175,95,15],[175,255,15],[255,95,15],[255,255,15],
    [15,95,95],[15,255,95],[95,95,95],[95,255,95],[175,95,95],[175,255,95],[255,95,95],[255,255,95],
    [15,95,175],[15,255,175],[95,95,175],[95,255,175],[175,95,175],[175,255,175],[255,95,175],[255,255,175],
    [15,95,255],[15,255,255],[95,95,255],[95,255,255],[175,95,255],[175,255,255],[255,95,255],[255,255,255]
];

var Toolbar = {
    $el: $('#toolbar'),

    noSearch: true,
    colors: [],

    initialize: function() {
        // Class events
        this
            .on('chooseColor', $.proxy(this.chooseColor, this))
            .on('removeColor', $.proxy(this.removeColor, this));

        // Click events
        $('body')
            .on('click', '#toolbar .colors ul li', $.proxy(this.clickColor, this))
            .on('click', '#toolbar .chosen-colors ul li', $.proxy(this.clickChosenColor, this));
        this.$el.find('button#search').hide()
            .on('click', $.proxy(this.clickButton, this));
        this.$el.find('button#add-more').hide()
            .on('click', $.proxy(this.addMore, this));

        // Resize events
        $(window).resize($.proxy(this.resizeEvent, this));

        this.addPalette();
    },

    // Event methods

    clickButton: function(e) {
        e.preventDefault();

        // Start searching photos matching the input colors
        this.searchColors();

        if (this.noSearch) {
            // If we are still on the landing page, remove it
            $('body').removeClass('no-filter');

            this.noSearch = false;

            // And do some resizing
            this._resizeChosenColors();
            Photos.$el.packery();
            $(window).resize();

            // Show/hide buttons
            this.$el.find('#add-more').show();
            this.$el.find('#search').hide();
        }
    },

    addMore: function(e) {
        if (this.colors.length) {
            // If the user has chosen any colors, reset them
            this._resetColors();
            // Reset the search results too
            Photos._resetPhotos();
        }

        // Start adding new photos
        this.$el.find('#add-more')
            .addClass('loadingNewPhotos');
        Photos.refreshPhotos();
    },

    clickColor: function(e) {
        var $color = $(e.target),
            red = $color.data('red'),
            green = $color.data('green'),
            blue = $color.data('blue');

        // The user has chosed a color, trigger chooseColor event
        this.trigger('chooseColor', [red, green, blue]);
    },

    clickChosenColor: function(e) {
        var $color = $(e.target),
            red = $color.data('red'),
            green = $color.data('green'),
            blue = $color.data('blue');

        // The user has removed a color, trigger removeColor event
        this.trigger('removeColor', [red, green, blue]);
    },

    resizeEvent: function() {
        if (!this.noSearch) {
            // If we are not on the landing page, resize the page, so that it is responsible
            Photos.$el.css('width', $(document).width() - this.$el.width());
        }
    },

    chooseColor: function() {
        // The user has chosed a color, add it to the chosen colors
        var color = Application._refineArguments(arguments);

        // The number of colors must not exceed 5
        if (this.colors.length > 4) return;
        // Add colors which have not been adder before
        if (this._getColorFromStorage(color).length) return;

        // Add it to chosen colors
        this.colors.push(color);

        // Create the color element and add it to DOM
        var $color = this._getColorElement(color),
            $chosenColors = this.$el.find('.chosen-colors');
        $color.appendTo($chosenColors.find('ul'));

        // Resize the colors, so that they are always centered in their container
        this._resizeChosenColors();

        $chosenColors.show();

        if (!this.noSearch) {
            // If we are not in the landing page, automatically start searching
            this.searchColors();
        } else {
            // We are in the landing page, wait for user's interaction with the search button
            this.$el.find('button#search').show();
        }
    },

    removeColor: function() {
        // The user has chosed a color to remove
        var chosenColor = Application._refineArguments(arguments);

        // Check if there are any colors to remove
        if (this.colors.length === 0) return;

        // Remove the color from the list
        this.colors = _.reject(this.colors, function(color) {
            return chosenColor[0] === color[0] &&
                    chosenColor[1] === color[1] &&
                    chosenColor[2] === color[2];
        });

        // Get the color element and remove it
        this._getColorFromEl(this.$el.find('.chosen-colors'), chosenColor).remove();

        // Resize the color element
        this._resizeChosenColors();

        if (this.colors.length === 0) {
            // If that was the last color, hide the container
            this.$el.find('.chosen-colors').hide();
            this.$el.find('button#search').hide();
        }

        // Add a loader only if we are not in the landing page
        if (!this.noSearch) Photos.printText('Loading...');

        var that = this;
        _.defer(function() {
            if (!that.noSearch) {
                // If we are not on the landing page, automatically refresh the results
                that.searchColors();
            }
        });
    },

    // Class methods

    addPalette: function() {
        var that = this;

        // Add each color from the palette in DOM
        _.each(Palette, function(color) {
            var $color = that._getColorElement(color);
            $color.appendTo(that.$el.find('.colors ul'));
        });
    },

    searchColors: function() {
        var searchQuery = [],
            colorsCount = this.colors.length;

        // Check if the number of colors is valid
        if (colorsCount <= 0 || colorsCount > 5) {
            if (!this.noSearch) {
                // If we are not in the landing page, refresh the page by resetting the photos
                // This method does that when there are not arguments supplied
                Photos.getMatchingPhotos();
            } else {
                // Otherwise prevent any other operations
                return;
            }
        }

        // Calculate the avarage color percentage
        var colorsPercentage = Math.floor(100/colorsCount);

        // Create the search query
        _.each(this.colors, function(color) {
            searchQuery.push(colorsPercentage);
            searchQuery.push(color[0]);
            searchQuery.push(color[1]);
            searchQuery.push(color[2]);
        });

        // And post the query to Imagga's API
        Photos.getMatchingPhotos(searchQuery);
    },

    _getColorElement: function(color) {
        var $color = $('<li>');
        $color
            .css('background-color', 'rgb('+color[0]+', '+color[1]+', '+color[2]+')')
            .data('red', color[0]).data('green', color[1]).data('blue', color[2]);

        return $color;
    },

    _resizeChosenColors: function() {
        var $chosenColors = this.$el.find('.chosen-colors');

        $chosenColors.find('ul')
            .css('margin-left', ($chosenColors.width() - ($chosenColors.find('ul li').width() + 2) * this.colors.length)/2);
    },

    _getColorFromEl: function($el, color) {
        return $el.find('ul li').filter(function() {
            var $li = $(this);
            return $li.data('red') === color[0] &&
                    $li.data('green') === color[1] &&
                    $li.data('blue') === color[2];
        });
    },

    _getColorFromStorage: function(color) {
        return _.filter(this.colors, function(c) {
            return color[0] === c[0] &&
                    color[1] === c[1] &&
                    color[2] === c[2];
        });
    },

    _resetColors: function() {
        this.$el.find('.chosen-colors').hide().find('ul').html('');
        this.colors = [];
    }
};

_.extend(Toolbar, Events);
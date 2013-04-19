###
# ownCloud
#
# @author Bernhard Posselt
# Copyright (c) 2012 - Bernhard Posselt <nukeawhale@gmail.com>
#
# This file is licensed under the Affero General Public License version 3 or later.
# See the COPYING-README file
#
###

angular.module('MultiInstance').factory 'MultiInstanceRequest',
['$http', 'Config', '_MultiInstanceRequest', 'Publisher',
($http, Config, _MultiInstanceRequest, Publisher) ->
	return new _MultiInstanceRequest($http, Config, Publisher)
]

angular.module('MultiInstance').factory 'ItemModel',
['_ItemModel',
(_ItemModel) ->
	return new _ItemModel()
]

angular.module('MultiInstance').factory 'Publisher',
['_Publisher', 'ItemModel',
(_Publisher, ItemModel) ->
	publisher = new _Publisher()
	publisher.subscribeModelTo(ItemModel, 'items')
	return publisher
]
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

angular.module('MultiInstance').controller 'ExampleController',
['$scope', 'Config', 'MultiInstanceRequest', '_ExampleController', 'ItemModel',
($scope, Config, MultiInstanceRequest, _ExampleController, ItemModel) ->
	return new _ExampleController($scope, Config, MultiInstanceRequest, ItemModel)
]
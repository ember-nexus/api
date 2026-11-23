module github.com/ember-nexus/api/lib/UuidToTypeMphfLookup/extension

go 1.27.0

require (
	github.com/dunglas/frankenphp v1.13.5
	github.com/ember-nexus/api/lib/UuidToTypeMphfLookup v0.0.0
)

// local development only: the image build maps both modules to the checked out sources, see docker/api/Dockerfile
replace github.com/ember-nexus/api/lib/UuidToTypeMphfLookup => ../

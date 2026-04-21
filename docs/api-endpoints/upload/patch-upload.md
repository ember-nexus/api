# <span class="title-url"><span class="method-patch">PATCH</span>` /upload/<uuid>`</span><span class="title-human">Patch Upload Endpoint</span>

This endpoint is used to upload a new chunk to the resumable upload.

The new chunk can be of zero length - acting similar to the `HEAD /upload/<uuid>` endpoint, but also enabling the user
to complete an upload without transmitting additional chunks.

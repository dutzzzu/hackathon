<?php
class RGA_Queue_Adapter_MongoDB_Message extends Shanty_Mongo_Document {
    protected static $_collection = 'queue_message';
}
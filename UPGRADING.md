## Upgrading from v1 to v2

The driver system has been refactored in v2. If you use the built-in `ConfigurationDriver` or `DatabaseDriver`, no changes are required.

If you have a custom driver, update it to implement the revised `Esign\EmailWhitelisting\Contracts\EmailWhitelistingDriverContract` interface.
Drivers now only provide email addresses; the whitelisting logic is handled by `Esign\EmailWhitelisting\Listeners\WhitelistEmailAddresses`.

Update your custom driver as follows:

```diff
- class CustomDriver extends AbstractDriver implements EmailWhitelistingDriverContract
+ class CustomDriver implements EmailWhitelistingDriverContract
{
-    public function redirectEmailAddresses(MessageSending $messageSendingEvent): void
-    {
-        // Custom logic to handle email redirection
-    }
+    public function redirectEmailAddresses(MessageSending $messageSendingEvent): Collection
+    {
+        // Return redirect email addresses
+        return collect([]);
+    }

-    public function whitelistEmailAddresses(MessageSending $messageSendingEvent): void
-    {
-        // Custom logic to handle whitelisting email addresses
-    }
+    public function whitelistEmailAddresses(MessageSending $messageSendingEvent): Collection
+    {
+        // Return whitelisted email addresses
+        return collect([]);
+    }
}
```

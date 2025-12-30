import Foundation
import SafariServices
import AuthenticationServices
import UIKit

// MARK: - Browser Function Namespace

/// Functions related to opening URLs in browsers
/// Namespace: "Browser.*"
enum BrowserFunctions {

    // MARK: - Browser.Open

    /// Open a URL in the system's default browser (Safari)
    /// Parameters:
    ///   - url: string - The URL to open
    /// Returns:
    ///   - success: boolean - True if successfully opened
    class Open: BridgeFunction {
        func execute(parameters: [String: Any]) throws -> [String: Any] {
            guard let urlString = parameters["url"] as? String else {
                throw BridgeError.invalidParameters("url is required")
            }

            guard let url = URL(string: urlString) else {
                throw BridgeError.invalidParameters("Invalid URL format")
            }

            print("Browser.Open called for URL: \(urlString)")

            var success = false
            let semaphore = DispatchSemaphore(value: 0)

            DispatchQueue.main.async {
                if UIApplication.shared.canOpenURL(url) {
                    UIApplication.shared.open(url, options: [:]) { opened in
                        success = opened
                        semaphore.signal()
                    }
                } else {
                    semaphore.signal()
                }
            }

            _ = semaphore.wait(timeout: .now() + 2)

            if success {
                print("Successfully opened URL in system browser")
            } else {
                print("Failed to open URL in system browser")
            }

            return ["success": success]
        }
    }

    // MARK: - Browser.OpenInApp

    /// Open a URL in an in-app browser (SFSafariViewController)
    /// Parameters:
    ///   - url: string - The URL to open
    /// Returns:
    ///   - success: boolean - True if successfully opened
    class OpenInApp: BridgeFunction {
        func execute(parameters: [String: Any]) throws -> [String: Any] {
            guard let urlString = parameters["url"] as? String else {
                throw BridgeError.invalidParameters("url is required")
            }

            guard let url = URL(string: urlString) else {
                throw BridgeError.invalidParameters("Invalid URL format")
            }

            print("Browser.OpenInApp called for URL: \(urlString)")

            var success = false
            let semaphore = DispatchSemaphore(value: 0)

            DispatchQueue.main.async {
                guard let windowScene = UIApplication.shared.connectedScenes.first as? UIWindowScene,
                      let rootViewController = windowScene.windows.first?.rootViewController else {
                    semaphore.signal()
                    return
                }

                let safariVC = SFSafariViewController(url: url)
                safariVC.modalPresentationStyle = .pageSheet

                // Find the topmost view controller
                var topVC = rootViewController
                while let presented = topVC.presentedViewController {
                    topVC = presented
                }

                topVC.present(safariVC, animated: true) {
                    success = true
                    semaphore.signal()
                }
            }

            _ = semaphore.wait(timeout: .now() + 2)

            if success {
                print("Successfully opened URL in in-app browser")
            } else {
                print("Failed to open URL in in-app browser")
            }

            return ["success": success]
        }
    }

    // MARK: - Browser.OpenAuth

    /// Open a URL in an authentication session (ASWebAuthenticationSession)
    /// This is a fire-and-forget function - it starts the auth session and returns immediately.
    /// The callback URL is handled via DeepLinkRouter which redirects the WebView.
    /// Parameters:
    ///   - url: string - The URL to open for authentication
    /// Returns:
    ///   - success: boolean - True if session was started successfully
    class OpenAuth: BridgeFunction {
        func execute(parameters: [String: Any]) throws -> [String: Any] {
            guard let urlString = parameters["url"] as? String else {
                throw BridgeError.invalidParameters("url is required")
            }

            guard let url = URL(string: urlString) else {
                throw BridgeError.invalidParameters("Invalid URL format")
            }

            print("Browser.OpenAuth called for URL: \(urlString)")

            // Fire-and-forget: dispatch to main queue and return immediately
            DispatchQueue.main.async {
                WebAuthManager.shared.startAuthSession(url: url)
            }

            return ["success": true]
        }
    }
}

// MARK: - Web Authentication Manager

/// Singleton manager for ASWebAuthenticationSession
/// Retains the session and context provider for the duration of the auth flow
final class WebAuthManager: NSObject, ASWebAuthenticationPresentationContextProviding {
    static let shared = WebAuthManager()

    private var activeSession: ASWebAuthenticationSession?

    private override init() {
        super.init()
    }

    /// Read NATIVEPHP_DEEPLINK_SCHEME from the app's .env file
    private func getDeeplinkScheme() -> String {
        let documentsURL = FileManager.default.urls(for: .documentDirectory, in: .userDomainMask).first!
        let envPath = documentsURL.appendingPathComponent("app/.env")

        guard FileManager.default.fileExists(atPath: envPath.path),
              let envContent = try? String(contentsOf: envPath, encoding: .utf8) else {
            print("No .env file found, using default deeplink scheme")
            return "native"
        }

        // Use regex to find NATIVEPHP_DEEPLINK_SCHEME value
        let pattern = #"NATIVEPHP_DEEPLINK_SCHEME\s*=\s*([^\r\n]+)"#
        if let regex = try? NSRegularExpression(pattern: pattern),
           let match = regex.firstMatch(in: envContent, range: NSRange(envContent.startIndex..., in: envContent)),
           let valueRange = Range(match.range(at: 1), in: envContent) {
            let value = String(envContent[valueRange])
                .trimmingCharacters(in: .whitespaces)
                .trimmingCharacters(in: CharacterSet(charactersIn: "\"'"))

            if !value.isEmpty {
                print("Found deeplink scheme in .env: \(value)")
                return value
            }
        }

        print("No NATIVEPHP_DEEPLINK_SCHEME found, using default: native")
        return "native"
    }

    func startAuthSession(url: URL) {
        // Cancel any existing session
        activeSession?.cancel()
        activeSession = nil

        guard let windowScene = UIApplication.shared.connectedScenes
            .compactMap({ $0 as? UIWindowScene })
            .first(where: { $0.activationState == .foregroundActive }) else {
            print("No active window scene for auth session")
            return
        }

        let callbackScheme = getDeeplinkScheme()

        let session = ASWebAuthenticationSession(url: url, callbackURLScheme: callbackScheme) { [weak self] callbackURL, error in
            // Clear the session reference
            self?.activeSession = nil

            if let error = error {
                if let authError = error as? ASWebAuthenticationSessionError,
                   authError.code == .canceledLogin {
                    print("User cancelled authentication session")
                } else {
                    print("Auth session error: \(error.localizedDescription)")
                }
                return
            }

            if let callbackURL = callbackURL {
                print("Auth session completed with callback: \(callbackURL.absoluteString)")
                // Route the callback URL through DeepLinkRouter to redirect the WebView
                DeepLinkRouter.shared.handle(url: callbackURL)
            }
        }

        session.presentationContextProvider = self
        session.prefersEphemeralWebBrowserSession = false

        // Retain the session
        activeSession = session

        if session.start() {
            print("Auth session started")
        } else {
            print("Failed to start auth session")
            activeSession = nil
        }
    }

    // MARK: - ASWebAuthenticationPresentationContextProviding

    func presentationAnchor(for session: ASWebAuthenticationSession) -> ASPresentationAnchor {
        return UIApplication.shared.connectedScenes
            .compactMap { $0 as? UIWindowScene }
            .first(where: { $0.activationState == .foregroundActive })?
            .windows
            .first { $0.isKeyWindow } ?? ASPresentationAnchor()
    }
}
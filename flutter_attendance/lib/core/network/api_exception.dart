class ApiException implements Exception {
  const ApiException(
    this.message, {
    this.isNetworkError = false,
    this.statusCode,
  });

  final String message;
  final bool isNetworkError;

  /// HTTP status behind the failure, when there was one. Lets a caller tell an
  /// expired session (401) apart from a transient network blip, which need
  /// very different handling mid-exam.
  final int? statusCode;

  bool get isSessionExpired => statusCode == 401;

  @override
  String toString() => message;
}

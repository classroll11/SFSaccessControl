using SourceAFIS;

namespace SFS_Access.Biometrics;

public sealed record FingerprintCandidate(int UserId, int Slot, string Finger, string Template, string Source = "huellas");

public sealed record FingerprintMatch(FingerprintCandidate Candidate, double Score);

public static class SourceAfisMatcher
{
    public const double RecommendedThreshold = 40;

    public static FingerprintMatch? Identify(
        FingerprintTemplate probe,
        IEnumerable<FingerprintCandidate> candidates,
        out int invalidTemplateCount)
    {
        ArgumentNullException.ThrowIfNull(probe);
        ArgumentNullException.ThrowIfNull(candidates);

        invalidTemplateCount = 0;
        FingerprintMatcher matcher = new(probe);
        FingerprintMatch? best = null;

        foreach (FingerprintCandidate candidate in candidates)
        {
            if (candidate.UserId <= 0 || string.IsNullOrWhiteSpace(candidate.Template))
            {
                invalidTemplateCount++;
                continue;
            }

            FingerprintTemplate template;
            try
            {
                template = new FingerprintTemplate(Convert.FromBase64String(candidate.Template));
            }
            catch (Exception ex) when (ex is FormatException
                or ArgumentException
                or InvalidOperationException
                or Dahomey.Cbor.CborException)
            {
                invalidTemplateCount++;
                continue;
            }

            double score = matcher.Match(template);
            if (best is null || score > best.Score)
                best = new FingerprintMatch(candidate, score);
        }

        return best is { Score: >= RecommendedThreshold } ? best : null;
    }
}

import { z } from 'zod';

// ---------------------------------------------------------------------------
// Register
// ---------------------------------------------------------------------------

export const registerSchema = z
  .object({
    name: z.string().min(2, 'Name must be at least 2 characters.').max(100),
    email: z.string().email('Enter a valid email address.'),
    password: z
      .string()
      .min(8, 'Password must be at least 8 characters.')
      .max(72),
    password_confirmation: z.string(),
    subscription_plan_id: z.number({ error: 'Select a plan.' }).int().positive(),
  })
  .refine((data) => data.password === data.password_confirmation, {
    message: 'Passwords do not match.',
    path: ['password_confirmation'],
  });

export type RegisterPayload = z.infer<typeof registerSchema>;

// ---------------------------------------------------------------------------
// Login
// ---------------------------------------------------------------------------

export const loginSchema = z.object({
  email: z.string().email('Enter a valid email address.'),
  password: z.string().min(1, 'Password is required.'),
  remember: z.boolean().optional(),
});

export type LoginPayload = z.infer<typeof loginSchema>;

// ---------------------------------------------------------------------------
// Forgot Password
// ---------------------------------------------------------------------------

export const forgotPasswordSchema = z.object({
  email: z.string().email('Enter a valid email address.'),
});

export type ForgotPasswordPayload = z.infer<typeof forgotPasswordSchema>;

// ---------------------------------------------------------------------------
// Reset Password
// ---------------------------------------------------------------------------

export const resetPasswordSchema = z
  .object({
    token: z.string().min(1),
    email: z.string().email('Enter a valid email address.'),
    password: z
      .string()
      .min(8, 'Password must be at least 8 characters.')
      .max(72),
    password_confirmation: z.string(),
  })
  .refine((data) => data.password === data.password_confirmation, {
    message: 'Passwords do not match.',
    path: ['password_confirmation'],
  });

export type ResetPasswordPayload = z.infer<typeof resetPasswordSchema>;

// ---------------------------------------------------------------------------
// Verify Email
// ---------------------------------------------------------------------------

export const verifyEmailSchema = z.object({
  token: z.string().min(1, 'Verification token is required.'),
  email: z.string().email(),
});

export type VerifyEmailPayload = z.infer<typeof verifyEmailSchema>;
